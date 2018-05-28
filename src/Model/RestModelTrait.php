<?php
namespace Ordent\RamenRest\Model;
use Illuminate\Http\UploadedFile;

trait RestModelTrait{
    
    public function getTransformer(){
        if(is_string($this->transformer)){
            return app($this->transformer);
        }
        return $this->transformer;
    }

    public function getRules($key = null)
    {
        if ($key != null && array_key_exists($key, $this->rules)) {
            return $this->rules[$key];
        } else {
            return [];
        }
    }

    protected function resolveUpload($files, $attribute){
        if(is_array($files)){
            $results = [];
            foreach ($files as $key => $value) {
                if(is_integer($key)){
                    array_push($results, $this->uploadFile($value, $attribute));
                }else{
                    $results = array_add($results, $key, $this->uploadFile($value, $attribute, $key));
                }
            }
            $results = json_encode($results);
        }else{
            $results = $this->uploadFile($files, $attribute);
        }

        return $results;
    }

    protected function uploadFile($data, $attribute, $key = null){
        if(is_null($key)){
            $meta = [
                'path' => \Request::input($attribute.'_path'),
                'width' => \Request::input($attribute.'_width'),
                'height' => \Request::input($attribute.'_height'),
                'type' => \Request::input($attribute.'_type')
            ];
        }else{
            $meta = [
                'path' => \Request::input($attribute.'_'.$key.'_path'),
                'width' => \Request::input($attribute.'_'.$key.'_width'),
                'height' => \Request::input($attribute.'_'.$key.'_height'),
                'type' => \Request::input($attribute.'_'.$key.'_type')
            ];
        }
        $fileProcessor = app('FileProcessor');
        return $fileProcessor->uploadFile($data, $meta['path'], $meta);
    }
}