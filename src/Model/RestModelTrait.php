<?php
namespace Ordent\RamenRest\Model;
use Illuminate\Http\UploadedFile;
use Ordent\RamenRest\Transformer\RestTransformer;

trait RestModelTrait{
    
    public function getTransformer(){
        if($this->transformer){
            if(is_string($this->transformer)){
                return app($this->transformer);
            }
            return $this->transformer;
        }
        return new RestTransformer;
    }

    public function getRules($key = null)
    {
        if(isset($this->rules)){
            if ($key != null && array_key_exists($key, $this->rules)) {
                return $this->rules[$key];
            }
        }
        return [];
    }

    public function resolveUpload($files, $attribute, $path = null, $disks = null, $meta = null){
        if(is_null($disks)){
            $disks = config('filesystems.default', 'public');
        }

        if(is_array($files)){
            $results = [];
            foreach ($files as $key => $file) {
                if(is_string($file)){
                    array_push($results, $file);
                }else{
                    array_push($results, $this->uploadFile($file, $attribute, null, $path, $disks, $meta));
                }
            }
            $results = $results;
        }else{
            $results = null;
            if(!is_null($files)){
                if(is_string($files)){
                    $results = $files;
                }else{
                    $results = $this->uploadFile($files, $attribute, null, $path, $disks, $meta);
                }
            }
        }

        return $results;
    }

    public function uploadFile($data, $attribute, $key = null, $path = null, $disks = null, $meta = null){
        if(is_null($key)){
            $attribute_key = $attribute;
        }else{
            $attribute_key = $attribute.'_'.$key.'-';            
        }

        $meta = null;
        if(!is_null(\Request::input($attribute.'_path')) || \Request::input($attribute.'_width') || \Request::input($attribute.'_height') || \Request::input($attribute.'_type')){
            $meta = [
                'path' => \Request::input($attribute.'_path'),
                'width' => \Request::input($attribute.'_width'),
                'height' => \Request::input($attribute.'_height'),
                'type' => \Request::input($attribute.'_type')
            ];
        }
        if(!is_null($path)){
            $meta['path'] = $path;
        }
        $fileProcessor = app('FileProcessor');
        
        return $fileProcessor->uploadFile($data, $meta['path'], $meta, $disks);
    }

    protected function getFile($data){
        if(strpos($data, 'http') !== false){
            return $data;
        }
        if(config('filesystems.default') == 'local'){
            return $data;
        }else if(config('filesystems.default') == 'public'){
            return asset('/storage/'.$data);
        }else{
            return $data;
        }
    }
}