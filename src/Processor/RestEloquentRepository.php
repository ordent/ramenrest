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

    private function resolveWhere($model, $fields)
    {
        
        if (count($fields)>0) {
            foreach ($fields as $i => $l) {
                if (substr($l, 0, 1) == ">" || substr($l, 0, 1) == "<") {
                    $model = $model->where($i, substr($l, 0, 1), substr($l, 1));
                } elseif (substr($l, 0, 1) == "!") {
                    $out = explode(",", substr($l, 1));
                    $model = $model->whereNotIn($i, $out);
                } elseif (substr($l, 0, 1) == "$") {
                    $model = $model->where($i, 'like', "%".substr($l, 1)."%");
                } else {
                    $in = explode(",", $l);
                    $model = $model->whereIn($i, $in);
                }
            }
        }
        
        return $model;
    }

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
        foreach ($files as $i => $f) {
            $string = [];
            if (is_array($f)) {
                foreach ($f as $j => $x) {
                    $x = event(new FileHandlerEvent($x, $i, $input));
                    if(is_array($x) && count($x) == 1){
                        $x = $x[0];
                    }
                    try{
                        if(is_string($x)){
                            array_push($string, $x);
                        }else{
                            array_push($string, asset('/storage/')."/".$x->store('images/'.$i, "public"));                        
                        }
                    }catch(FatalThrowableError $e){
                        abort(422, 'There\'s something wrong with the image you send. Please check property '.$i);
                    }
                }
            } else {
                $f = event(new FileHandlerEvent($f, $i, $input));
                if(is_array($f) && count($f) == 1){
                    $f = $f[0];
                }
                try{
                    if(is_string($x)){
                        array_push($string, $x);                        
                    }else{
                        array_push($string, asset('/storage/')."/".$f->store('images/'.$i, "public"));                    
                    }
                }catch(FatalThrowableError $e){
                    abort(422, 'There\'s something wrong with the image you send. Please check property '.$i);
                }
            }
            if (count($string)>1) {
                $input[$i] = $string;
            } else {
                $input[$i] = $string[0];
            }
        }
        return $input;
    }
}
