<?php
namespace Ordent\RamenRest\Model;
use Illuminate\Http\UploadedFile;
use Ordent\RamenRest\Transformer\RestTransformer;

/**
 * RamenRestModel trait
 * 
 * A trait to add into basic laravel model, this trait is needed to make sure the REST can work as seamless as possible
 * @copyright 2018 Orde Digital Intelektual (ORDENT)
 * @author Dimas Satrio <dimassrio@gmail.com>
 * @package Ordent\RamenRest\Model
 */
trait RamenRestModelTrait{
    
    /**
     * ramenGetTransformer function
     *
     * get the transformer and instantiate it (if it hasn't) if it already listed on model.
     * 
     * @return Ordent\RamenRest\Transformer\RestTransformer
     */
    public function ramenGetTransformer(){
        if($this->transformer){
            if(is_string($this->transformer)){
                return app($this->transformer);
            }
            return $this->transformer;
        }
        return new RestTransformer;
    }

    /**
     * ramenGetRules function
     *
     * get array of rules that already listed on model
     * 
     * @param mixed $key 
     * (store, update, delete, null)
     * @return array
     */
    public function ramenGetRules($key = null)
    {
        if(isset($this->rules)){
            if ($key != null && array_key_exists($key, $this->rules)) {
                return $this->rules[$key];
            }
        }
        return [];
    }

    /**
     * ramenResolveUpload function
     *
     * Use this function to upload the file into your model, it will return either an array of url string or just one url string.
     * 
     * @param Illuminate\Http\UploadedFile $files
     * file that you need to upload
     * @param string $attribute
     * the properties of the file in the model
     * @param string $path
     * path to store the file
     * @param string $disks
     * type of uploading (public, gcp, aws)
     * in order to use s3 as a disk, you must add following dependency to your project. 'composer require league/flysystem-aws-s3-v3'
     * @param array $meta
     * if you want to add any meta into the response
     * @param bool $complex
     * insert true if you want the file processor to handle it for performance purposes.
     * 
     * @return mixed
     */
    public function ramenResolveUpload($files, $attribute, $path = null, $disks = null, $meta = null, $complex = false){
        if(is_null($disks)){
            $disks = config('filesystems.default', 'public');
        }

        if(is_array($files)){
            $results = [];
            foreach ($files as $key => $file) {
                if(is_string($file)){
                    array_push($results, $file);
                }else{
                    array_push($results, $this->uploadFile($file, $attribute, $path, $disks, $meta, $complex));
                }
            }
            $results = $results;
        }else{
            $results = null;
            if(!is_null($files)){
                if(is_string($files)){
                    $results = $files;
                }else{
                    $results = $this->uploadFile($files, $attribute, $path, $disks, $meta, $complex);
                }
            }
        }

        return $results;
    }

    /**
     * ramenUploadFile function
     * 
     * main function to uploading to the system, exposed so people can access it directly rather than using standard resolver
     * in order to use s3 as a disk, you must add following dependency to your project. 'composer require league/flysystem-aws-s3-v3'
     *
     * @param mixed $data
     * @param mixed $attribute
     * @param mixed $key
     * @param mixed $path
     * @param mixed $disks
     * @param mixed $meta
     * @param mixed $complex
     * @return void
     */
    public function ramenUploadFile($data, $attribute, $path = null, $disks = null, $meta = null, $complex = false){
        $meta = $this->ramenMetaFilesGenerator($meta, $path, $complex, $attribute);
        
        $fileProcessor = app('FileProcessor');
        
        return $fileProcessor->uploadFile($data, $path, $meta, $disks, $complex);
    }

    /**
     * ramenMetaFilesGenerator
     *
     * @param mixed $meta
     * @param mixed $path
     * @param mixed $complex
     * @return array
     */
    protected function ramenMetaFilesGenerator($meta, $path, $complex, $attribute){
        if($meta == null){
            $meta = [];
        }
        if(!is_null(\Request::input($attribute.'_path'))){
            $meta['path'] = \Request::input($attribute.'_path');
        }
        if(!is_null(\Request::input($attribute.'_width'))){
            $meta['width'] = \Request::input($attribute.'_width');
        }
        if(!is_null(\Request::input($attribute.'_height'))){
            $meta['height'] = \Request::input($attribute.'_height');
        }
        if(!is_null(\Request::input($attribute.'_type'))){
            $meta['type'] = \Request::input($attribute.'_type');
        }
        if(!is_null($path)){
            $meta['path'] = $path;
        }
        if($complex){
            $meta['extension'] = $data->getClientOriginalExtension();
            $meta['size'] = $data->getClientSize();
        }
        return $meta;
    }

    /**
     * ramenGetFile
     *
     *  use this function to get the url path of the file that you have already uploaded
     * 
     * @param mixed $data
     * @param mixed $complex
     * @return string
     */
    protected function ramenGetFile($data, $complex = false, $disks = null){
        if($complex && !is_null($data)){
            // json parse if $data is a json
            try{
                $data = json_decode($data);
            }catch(\Exception $e){
                
            }
            $data->original = $this->parseFile($data->original, $disks);
            $data->thumbnail = $this->parseFile($data->thumbnail, $disks);
            
            return $data;
        }else{
            return $this->parseFile($data, $disks);
        }
    }
    
    /**
     * ramenParseFile function
     *
     * The parser for get file, it will change your url location based on the config.
     * 
     * @param mixed $data
     * @param string $disks
     * use this parameter to overwrite config information
     * @return string
     */
    protected function ramenParseFile($data, $disks = null){
        // return the string at it is if it already have http / https inside of it.
        if(strpos($data, 'http') !== false){
            return $data;
        }
        if(is_null($disks)){
            $type = config('filesystems.default');
        }else{
            $type = $disks;
        }

        switch ($type) {
            case 'local':
                return $data;
                break;
            case 'public':
                return asset('/storage/'.$data);
                break;
            default:
                return \Storage::url($data);
                break;
        }
    }
}