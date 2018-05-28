<?php
namespace Ordent\RamenRest\Processor;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
class FileProcessor{
    protected $path = '/images/';

    public function setPath($path){
        $this->path = $path;
    }

    public function getPath(){
        return $this->path;
    }

    public function resolvePath($path){
        if(is_null($path) || $path == ''){
            return $this->path;
        }else{
            return $path;
        }
    }
    
    public function uploadFile(UploadedFile $file, $path, $meta = null){
        $extension = $file->clientExtension();
        if($extension == 'png' || $extension == 'jpeg'){
            return $this->uploadImageFile($file, $path, $meta);
        }
        return $this->uploadNormalFile($file, $path, $meta);
    }

    public function uploadNormalFile(UploadedFile $file, $path = 'files', $meta = null){
        return asset("storage").'/'.$file->store($this->resolvePath($path), "public");
    }

    public function uploadImageFile(UploadedFile $file, $path = 'images', $meta = null){
        $data = $file->store($this->resolvePath($path), "public");
        $image = Image::make(public_path().'/storage/'.$data);
        if(!is_null($meta)){
            $width = array_get($meta, 'width', null);
            $height = array_get($meta, 'height', null);
            
            $type = array_get($meta, 'type', 'fit'); // resize crop fit
            if(is_null($type)){
                $type = 'fit';
            }
            if(!is_null($width) && !is_null($height)){
                if($type == 'crop'){
                    $image->crop($width, $height);
                }
                if($type == 'resize'){
                    $image->resize($width, $height);
                }
                if($type == 'fit'){
                    $image->fit($width, $height);
                }
            }
        }
        $image->save(public_path().'/storage/'.$data);
        $result = asset('/storage/').'/'.$data;
        return $result;
    }
}