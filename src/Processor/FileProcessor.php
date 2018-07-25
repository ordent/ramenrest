<?php
namespace Ordent\RamenRest\Processor;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
class FileProcessor{
    protected $path = '/images';

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
    
    public function uploadFile(UploadedFile $file, $path, $meta = null, $disks = 'public'){
        $extension = $file->clientExtension();
        if($extension == 'png' || $extension == 'jpeg'){
            return $this->uploadImageFile($file, $path, $meta, $disks);
        }
        return $this->uploadNormalFile($file, $path, $meta, $disks);
    }

    public function uploadNormalFile(UploadedFile $file, $path = 'files', $meta = null, $disks = 'public'){
        return \Storage::url($file->store($this->resolvePath($path), $disks));
    }

    public function uploadImageFile(UploadedFile $file, $path = 'images', $meta = null, $disks = 'public'){
        $data = $file->store($this->resolvePath($path), $disks);
        $temp = explode($data, '/');
        $filename = $temp[count($temp) - 1];
        if(!is_null($meta)){
            if($disks == 'public' || $disks == 'local'){
                $image = Image::make(storage_path().\Storage::url($data));                
            }else{
                $image = Image::make(\Storage::url($data));
            }
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
            if($disks == 'public' || $disks == 'local'){
                $image->save(storage_path().\Storage::url($data));
            }else{
                \Storage::put($data, $image->stream());
            }
        }
        $result = \Storage::url($data);
        return $result;
    }
}