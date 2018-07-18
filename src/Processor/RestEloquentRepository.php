<?php
namespace Ordent\RamenRest\Processor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Ordent\RamenRest\Events\FileHandlerEvent;
use Illuminate\Http\UploadedFile;
class RestEloquentRepository
{
    protected $model = null;

    public function setModel(Model $model){
        $this->model = $model;
    }
    public function getModel(){
        return $this->model;
    }
    public function getItem($id){
        if (is_numeric($id)) {
            return $this->model->findOrFail($id);
        }else{
            $filtered = array_keys(array_filter($this->model->getAttributes(), function($key){ return (strpos($key, 'slug') === 0); }, ARRAY_FILTER_USE_KEY));
            foreach ($filtered as $key) {
                $result = $this->model->where($key, $id)->first();
                // need to do it like this, since only return when slug found
                if($result != null){
                    return $result;
                }
            }
            return abort(404, 'Entity slug not found');
        }
    }

    public function postItem($parameters){
        return $this->model->create($parameters)->refresh();
    }

    
    public function putItem($id, $parameters = []){
        $result = null;
        // $result = $this->model->findOrFail($id);
        
        if(is_numeric($id)){
            $result = $this->model->findOrFail($id);
        }else{
            $sluggable = array_where($this->model->getFillable(), function($value, $key){
                return substr($value, 0, 4) == 'slug';
            });
            $result = $this->model;
            foreach($sluggable as $slug){
                $result = $result->orWhere($slug, $id);
            }
            $result = $result->get()->first();
        }

        $result->update($parameters);

        return $result->refresh();
    }

    public function deleteItem($id, $parameters){
        if (array_key_exists("soft", $parameters)) {
            if ($parameters["soft"]) {
                $this->model->findOrFail($id)->delete();
            } else {
                $this->model->findOrFail($id)->forceDelete();
            }
        } else {
            $this->model->findOrFail($id)->forceDelete();
        }
        return [];
    }

    public function getCollection($attributes, $orderBy){
        $model = $this->model;
        $exception = [];
        if(method_exists($model, 'getWhereExcept')){
            $exception = $model->getWhereExcept();
        }

        $attributes = array_except($attributes, $exception);
        $model = $this->resolveWhere($model, $attributes);
        
        $model = $this->resolveOrderBy($model, $orderBy);

        return $model;
    }

    public function getDatatables($attributes){
        $model = $this->model;
        $model = $this->resolveDatatable($model, $attributes);

        return $model;
    }

    private function resolveDatatable($model, $attributes){
        // parse column
        
        $parsed = array_except($attributes, config('ramen.reserved_datatable_process'));
        // dd($attributes);
        $relation = [];
        if(array_key_exists('relation', $attributes)){
            array_push($relation, $attributes['relation']);
        }
        if(array_key_exists('with', $attributes)){
            array_push($relation, $attributes['with']);
        }
        $model = $this->resolveWhere($model, $parsed);
       
        if(array_key_exists('search', $attributes)){
            if(!is_null($attributes['search']['value'])){
                $search = $attributes['search'];
                $search = $search['value'];
                
                //$search['value'];
                $count = [];
                // hacks to clearing where chaining from before
                $model = $model->where('id', -1);
                foreach($attributes['columns'] as $index => $columns){
                    if(filter_var($columns['searchable'], FILTER_VALIDATE_BOOLEAN) && !is_null($columns['data'])){
                        if(!strpos($columns['data'], $search)){
                                if(is_numeric($search)){
                                    $model = $model->orWhere($columns['data'], $search);
                                }else{
                                    if($model->getConnection()->getDriverName() == 'mysql'){
                                        $model = $model->orWhere($columns['data'], 'like', '%'.$search.'%');
                                    }else{
                                        $model = $model->orWhere($columns['data'], 'ilike', '%'.$search.'%');
                                    }
                                }
                        }
                    }
                    if(!is_null($columns['data'])){
                        // check the relation if columns to search have '.'
                        $relationCheck = explode('.', $columns['data']);
                        if(count($relationCheck) > 1){
                            $rel = $relationCheck[0];
                            $relCol = $relationCheck[count($relationCheck) - 1];

                            $relColType = \DB::connection()->getDoctrineColumn($model->getRelation($rel)->getRelated()->getTable(), $relCol)->getType()->getName();
                            if($relColType == 'string'){
                                $model = $model->with($rel)->orWhereHas($rel, function($q) use($relCol, $search, $model) {
                                    if($model->getConnection()->getDriverName() == 'mysql'){
                                        $q->where($relCol, 'like', '%'.$search.'%');                                        
                                    }else{
                                        $q->where($relCol, 'ilike', '%'.$search.'%');
                                    }
                                });
                            }
                        }
                    }
                }
            }
        }

        if(array_key_exists('order', $attributes)){
            $columns = $attributes['columns'];
            $orders = $attributes['order'];

            foreach($orders as $order){
                if($columns[$order['column']]['data']){
                    $model = $model->orderBy($columns[$order['column']]['data'], $order['dir']);                
                }
            }
        }
        
        return $model;
    }

    private function resolveComparison($model , $attribute, $query){
        return $model->where($attribute, $this->resolveOperator($query), $this->resolveAttribute($query));        
    }

    private function resolveBetween($model , $attribute, $query){
        return $model->whereBetween($attribute, explode(",", $this->resolveAttribute($query)));
    }

    private function resolveJson($model, $attribute, $query){
        $out = explode("}", $this->resolveAttribute($query));
        $identifier = $this->resolveJsonOperator($out[0]);
        $path = $this->resolveJsonAttribute($out[0]);
        $key = $this->buildJsonPath($attribute, $path);
        if($identifier ==  "=" || $identifier ==  ">" || $identifier == "<" || $identifier == "<=" || $identifier == ">="){
            $model = $model->where($key, $identifier, $out[1]);
        }else if($identifier == "|"){
            $range = explode(",", $out[1]);
            $model = $model->whereBetween($key, $range);
        }
        return $model;
    }

    private function resolveNot($model, $attribute, $query){
        return $model->whereNotIn($attribute, explode(',', substr($query, 1)));
    }

    private function resolveLike($model, $attribute, $query){
        if($model->getConnection()->getDriverName() == 'mysql'){
            return $model->where($attribute, 'like', "%".substr($query, 1)."%");
        }else{
            return $model->where($attribute, 'ilike', "%".substr($query, 1)."%");
        }
    }

    private function resolveClassModel($model, $attribute, $query){
        $path = explode(':', $query);
        if(count($path) < 4){
            abort(500, 'You need to specify the field to search after the model = ;Namespace;Model:$fieldToSearch:$fieldToReturn:$value || ;Namespace;Model:$fieldToSearch:$fieldToReturn:$relation:$value. It will return the collection of field from field to find.');
        }
        $result = [];
        $modelPath = str_replace(";", "\\", $path[0]);        
        try{
            $fieldToSearch = $path[1];
        }catch(\Exception $e){
            abort(500, 'You need to specify the field to search after the model = ;Namespace;Model:$fieldToSearch:$fieldToReturn:$value || ;Namespace;Model:$fieldToSearch:$fieldToReturn:$relation:$value. It will return the collection of field from field to find.');
        }
    }
    // search value based on other model result
    private function resolveSearchOtherModel($model, $attribute, $query){
        $path = explode(":", $query);

        if(count($path) < 4){
            abort(500, 'You need to specify the field to search after the model = ;Namespace;Model:$fieldToSearch:$fieldToReturn:$value || ;Namespace;Model:$fieldToSearch:$fieldToReturn:$relation:$value. It will return the collection of field from field to find.');
        }
        
        if(count($path) == 4){
            list($targetModel, $targetField, $targetResult, $targetValue) = $path;
        }

        if(count($path) == 5){
            list($targetModel, $targetField, $targetResult, $targetRelation, $targetValue) = $path;
        }
                    
        $targetModel = app(str_replace(";", "\\", $targetModel));
        $result = [];
        $value = $path[count($path) - 1];
        // return collection of model
        $targetMultiple = count(explode(',',$value))>1;
        
        if($targetMultiple){
            $value = explode(',',$value);
            foreach($value as $key => $val){
                if($key == 0){
                    if(is_numeric($val)){
                        $targetModel = $targetModel->where($targetField, $val);                                     
                    }else{
                        if($targetModel->getConnection()->getDriverName() == 'mysql'){
                            $targetModel = $targetModel->where($targetField, 'like', '%'.$val.'%');
                        }else{
                            $targetModel = $targetModel->where($targetField, 'ilike', '%'.$val.'%');
                        }
                        
                    }
                }else{
                    if(is_numeric($val)){
                        $targetModel = $targetModel->orWhere($targetField, $val);                                     
                    }else{
                        if($targetModel->getConnection()->getDriverName() == 'mysql'){
                            $targetModel = $targetModel->orWhere($targetField, 'like', '%'.$val.'%');
                        }else{
                            $targetModel = $targetModel->orWhere($targetField, 'ilike', '%'.$val.'%');
                        }
                    }
                }
            }
            $targetModel = $targetModel->get();
        }else{
            if(is_numeric($value)){
                $targetModel = $targetModel->where($targetField, $value)->get();
            }else{
                if($targetModel->getConnection()->getDriverName() == 'mysql'){
                    $targetModel = $targetModel->where($targetField, 'like', '%'.$value.'%')->get();
                }else{
                    $targetModel = $targetModel->where($targetField, 'ilike', '%'.$value.'%')->get();
                }
                    
            }
        }
        if(count($path) == 4){
            foreach($targetModel as $targetModelValue){
                array_push($result, $targetModelValue->{$targetResult});
            }
        }
        if(count($path) == 5){
            foreach($targetModel as $targetModelValue){
                foreach($targetModelValue->{$relation} as $data){
                    array_push($result, $data->{$targetResult});
                }
            }
        }
        
        return $model->whereIn($attribute, $result);
        
    }

    public function resolveScope($model, $attribute, $query){
        $path = explode(";", $query);
        foreach ($path as $key => $value) {
                $method = null;
                $param = null;
                if(count(explode(":", $value))>1){
                    list($method, $param) = explode(":", $value);
                }
                try{
                    if($param != null){
                        $param = explode(",", $param);
                        $model = $model->{$method}($param);
                    }else{
                        $model = $model->{$value}();
                    }
                }catch(\BadMethodCallException $e){
                        throw new \BadMethodCallException;
                }
            }
        return $model;
    }
    public function resolveSearchRelation($model, $attribute, $query){
        $withTemp = explode("^", $attribute);
        if(count($withTemp) > 1){
            list($relation, $targetField) = $withTemp;
            $model = $model->with($relation)->whereHas($relation, function($q) use($targetField, $query, $model){
                if($model->getConnection()->getDriverName() == 'mysql'){
                    $q->where($targetField, "like", "%".$attribute."%");
                }else{
                    $q->where($targetField, "ilike", "%".$attribute."%");
                }
            });
        }
        return $model;
    }

    public function resolveSearchMultiple($model, $attribute, $query){
        $in = explode(",", $query);
        return $model->whereIn($attribute, $in);
    }
    /**
     * resolveWhere 
     * convert param query into eloquent comparison based on specified format
     *
     * @param Model $model
     * @param Array $fields
     * @return $model
     */
    private function resolveWhere($model, $fields)
    {
        // check if there's any valid query param
        if (count($fields) > 0) {
            // loop each fields
            foreach ($fields as $i => $l) {
                // usecase more or less than (field=>value || field=<value)
                if ($this->resolveOperator($l) == ">" || $this->resolveOperator($l) == "<" || $this->resolveOperator($l) == "<=" || $this->resolveOperator($l) == ">=") {
                    $model = $this->resolveComparison($model, $i, $l);
                // usecase between  range (field=|min,max)
                } elseif($this->resolveOperator($l) == "|"){
                    $model = $this->resolveBetween($model, $i, $l);
                // usecase json path for searching json datatype (field={a,b,c=}value ==> field->a->b->c==value) // {a,b,c=} {a,b,c>} {a,b,c<} {a,b,c|}
                } elseif($this->resolveOperator($l) == "{"){
                    $model = $this->resolveJson($model, $i, $l);
                // usecase not in (field=!value)
                } elseif ($this->resolveOperator($l) == "!") {
                    $model = $this->resolveNot($model, $i, $l);
                // ilike operator (field=$value)
                } elseif ($this->resolveOperator($l) == "$") {
                    $model = $this->resolveLike($model, $i, $l);
                // get relation with path (field=App;User:rel:value) == field = [App\\User->rel]
                } elseif ($this->resolveOperator($l) == ";"){
                    $model = $this->resolveSearchOtherModel($model, $i, $l);
                // usecase scope
                } elseif ($i == "scope"){
                    $model = $this->resolveScope($model, $i, $l);
                // usecase search relation 
                } elseif(strpos($i, '^') !== false){
                    $model = $this->resolveSearchRelation($model, $i, $l);
                // usecase where in and where
                }else{
                    $model = $this->resolveSearchMultiple($model, $i, $l);
                }
            }
        }
        
        return $model;
    }
    /**
     * resolve orderBy
     * convert array into model orderBy comparison
     * @param Eloquent $model
     * @param Array $orderBy
     * @return $model
     */
    private function resolveOrderBy($model, $orderBy)
    {
        if (!is_null($orderBy)) {
            $orderBy = explode(",", $orderBy);
            foreach ($orderBy as $i => $o) {
                if ($this->resolveOperator($o) == '<') {
                    $model = $model->orderBy($this->resolveAttribute($o), "desc");
                } else {
                    $model = $model->orderBy($this->resolveAttribute($o), "asc");
                }
            }
        }
        return $model;
    }

    private function resolveOperator($value){
        if(substr($value, 0, 2) == ">=" || substr($value, 0, 2) == "<="){
            return substr($value, 0, 2);
        }else{
            return substr($value, 0, 1);
        }
    }

    private function resolve2Operator($value){
        return substr($value, 0, 2);
    }

    private function resolveAttribute($value){
        if($this->resolveOperator($value) == ">=" || $this->resolveOperator($value) == "<="){
            return substr($value, 2);
        }else{
            return substr($value, 1);
        }
    }

    private function resolveJsonOperator($value){
        if(substr($value, -2) == "<=" || substr($value, -2) == ">="){
            return substr($value, -2);
        }
        return substr($value, -1);
    }

    private function resolveJsonAttribute($value){
        if($this->resolveJsonOperator($value) == "<=" || $this->resolveJsonOperator($value) == ">="){
            return explode(',', substr($value, 0, -2));
        }
        return explode(',', substr($value, 0, -1));
    }

    private function buildJsonPath($attribute, $path){
        if(count($path) > 0){
            $key = $attribute;
            foreach ($path as $k => $p) {
                $key = $key . "->" . $p; 
            }
        }else{
            $key = $attribute."->".$path;
        }
        return $key;
    }
}