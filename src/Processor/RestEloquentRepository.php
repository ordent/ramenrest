<?php
namespace Ordent\RamenRest\Processor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Ordent\RamenRest\Events\FileHandlerEvent;
class RestEloquentRepository
{
    protected $model = null;
    public function setModel(Model $model)
    {
        $this->model = $model;
    }
    public function getItem($id)
    {
        if (is_numeric($id)) {
            return $this->model->findOrFail($id);
        }
    }

    public function postItem($parameters)
    {
        $files = $this->getFilesParameter($parameters);
        $input = $this->getNonFilesParameter($parameters);
        $input = $this->resolveUpload($files, $input);
        return $this->model->create($input);
    }

    private function getFilesParameter($parameters)
    {
        $files = [];
        if (method_exists($this->model, "getFiles")) {
            $files = array_only($parameters, $this->model->getFiles());
        }
        return $files;
    }

    private function getNonFilesParameter($parameters)
    {
        $input = [];
        if (method_exists($this->model, "getFiles")) {
            $input = array_except($parameters, $this->model->getFiles());
        } else {
            $input = $parameters;
        }
        return $input;
    }
    
    public function putItem($id, $parameters)
    {
        $files = $this->getFilesParameter($parameters);
        $input = $this->getNonFilesParameter($parameters);
        $input = $this->resolveUpload($files, $input);
        $result = $this->model->findOrFail($id);
        
        $result->update($input);

        return $result;
    }

    public function deleteItem($id, $parameters)
    {
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

    public function getCollection($attributes, $orderBy)
    {
        $model = $this->model;
        
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
                                    $model = $model->orWhere($columns['data'], 'like', '%'.$search.'%');
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
        if (count($fields)>0) {
            // loop each fields
            foreach ($fields as $i => $l) {
                // usecase more or less than (field=>value || field=<value)
                if (substr($l, 0, 1) == ">" || substr($l, 0, 1) == "<") {
                    $model = $model->where($i, substr($l, 0, 1), substr($l, 1));
                // usecase between  range (field=|min,max)
                } elseif(substr($l, 0, 1) == "|"){
                    $out = explode(",", substr($l, 1));
                    $model = $model->whereBetween($i, $out);
                // usecase json path for searching json datatype (field={a,b,c=}value ==> field->a->b->c==value) // {a,b,c=} {a,b,c>} {a,b,c<} {a,b,c|}
                } elseif(substr($l, 0, 1) == "{"){
                    $out = explode("}", substr($l, 1));
                    $identifier = substr($out[0], -1);
                    $out[0] = substr($out[0], 0, -1);
                    $path = explode(",", $out[0]);
                    $key = "";
                    if(count($path) > 0){
                        $key = $i;
                        foreach ($path as $k => $p) {
                            $key = $key . "->" . $p; 
                        }
                    }else{
                        $key = $i."->".$path;
                    }
                    
                    if($identifier ==  "="){
                        $model = $model->where($key, $out[1]);
                    }else if($identifier ==  ">" || $identifier == "<"){
                        $model = $model->where($key, $identifier, $out[1]);
                    }else if($identifier == "|"){
                        $range = explode(",", $out[1]);
                        $model = $model->whereBetween($key, $range);
                    }
                // usecase not in (field=!value)
                } elseif (substr($l, 0, 1) == "!") {
                    $out = explode(",", substr($l, 1));
                    $model = $model->whereNotIn($i, $out);
                // like operator (field=$value)
                } elseif (substr($l, 0, 1) == "$") {

                    if(env('DB_CONNECTION') == 'pgsql'){
                    $model = $model->where($i, 'ilike', "%".substr($l, 1)."%");
                    } else {
                    $model = $model->where($i, 'like', "%".substr($l, 1)."%");
                    }
                // get relation with path (field=App;User:rel:value) == field = [App\\User->rel]
                } elseif (substr($l, 0, 1) == ";"){
                    $path = explode(":", $l);
                    $rel = $path[1];
                    $model_path = str_replace(";", "\\", $path[0]);
                    $id = [];    
                
                    if(count($path == 4)){
                        $prop = $path[2];
                        $value = $path[3];
                        if(env('DB_CONNECTION') == 'pgsql'){
                            $temp = app($model_path)->where($prop, 'ilike', '%'.$value.'%')->get();
                        } else {
                            $temp = app($model_path)->where($prop, 'like', '%'.$value.'%')->get();
                        }
                        foreach($temp as $t){
                            if(is_null($t->{$rel}[0])){
                                array_push($id, $t->{$rel}->{$i});
                            }else{
                                foreach ($t->{$rel} as $key => $col) {
                                    array_push($id, $col->{$i});
                                }
                            }   
                        }
                    }else{
                        $value = $path[2];
                        $temp = app($model_path)->find($value);
                        if(!is_null($temp)){
                            $id = $temp->{$rel}->pluck("id")->all();
                        }
                    }

                    $model = $model->whereIn($i, $id);
                // find by relation
                // } else if ($i == "searchByRelation"){
                //     $path = explode(":", $l);
                //     $rel = $path[1];
                //     $value = $path[2];
                //     $path = str_replace(";", "\\", $path[0]);
                //     $temp = app($path)->where($rel, $value);
                //     if(!is_null($temp)){

                //     }
                } elseif ($i == "scope"){
                    $path = explode(";", $l);
                    foreach ($path as $key => $value) {
                        $val = explode(":", $value);
                        try{
                            if(count($val)>1){
                                $arr = explode(",", $val[1]);
                                $model = $model->{$val[0]}($arr);
                            }else{
                                $model = $model->{$value}();
                            }
                        }catch(\BadMethodCallException $e){
                        
                        }
                    }
                // usecase where and whereIn (field = a,b,c)
                } else {
                    $in = explode(",", $l);
                    $model = $model->whereIn($i, $in);
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
                if (substr($o, 0, 1) == "<") {
                    $model = $model->orderBy(substr($o, 1), "desc");
                } else {
                    $model = $model->orderBy(substr($o, 1), "asc");
                }
            }
        }
        return $model;
    }

    private function resolveUpload($files, $input)
    {
        $string = [];
        // process all the file type input
        foreach ($files as $i => $file) {
            $string = [];
            if(is_null($file)){
                if (is_array($file)) {
                    // if the file that got sent are a form
                    foreach ($file as $j => $item) {
                        if(!is_string($item)){
                            $temp = $item;
                            $item = event(new FileHandlerEvent($item, $i, $input));
                            if(is_array($item) && count($item) == 1){
                                $item = $item[0];
                            }else if(is_array($item) && count($item) == 0){
                                $item = $temp;
                            }
                        }
                        try{
                            if(is_string($item)){
                                array_push($string, $item);
                            }else{
                                array_push($string, asset('/storage/')."/".$item->store('images/'.$i, "public"));                        
                            }
                        }catch(FatalThrowableError $e){
                            abort(422, 'There\'s something wrong with the image you send. Please check property '.$i);
                        }
                    }
                } else {
                    if(!is_string($file)){
                        $temp = $file;
                        $file = event(new FileHandlerEvent($file, $i, $input));
                        if(is_array($file) && count($file) == 1){
                            $file = $file[0];
                        }else if(is_array($file) && count($file) == 0){
                            $file = $temp;
                        }
                    }
                    try{
                        if(is_string($file)){
                            array_push($string, $file);                        
                        }else{
                            array_push($string, asset('/storage/')."/".$file->store('images/'.$i, "public"));                    
                        }
                    }catch(FatalThrowableError $e){
                        abort(422, 'There\'s something wrong with the image you send. Please check property '.$i);
                    }
                }
                // check if theres any old images that need to be persist
                if(array_key_exists('_old_'.$i, $input)){
                    $old = $input['_old_'.$i];
                    if(!is_array($old)){
                        $old = [$old];
                    }
                    $string = array_merge($old, $string);
                }
                // insert images result into input
                if (count($string)>1) {
                    $input[$i] = $string;
                } else {
                    $input[$i] = $string[0];
                }
            }
        }
        return $input;
    }
}