<?php
namespace Ordent\RamenRest\Processor;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;

class FileProcessor
{
    protected $path = '/images';

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function resolvePath($path)
    {
        if (is_null($path) || $path == '') {
            return $this->path;
        } else {
            return $path;
        }
    }

    public function getRealPath($path, $disks)
    {
        if ($disks == 'public') {
            return storage_path() . '/app/public/' . $path;
        } else if ($disks == 'local') {
            return storage_path() . '/app/' . $path;
        } else {
            return \Storage::url($path);
        }
    }

    public function getFileName($path)
    {
        $temp = explode('/', $path);
        return $temp[count($temp) - 1];
    }

    public function storeFile(UploadedFile $file, $path = '/uploads', $disks = 'public', $complex = false)
    {
        $completePath = $this->getRealPath($this->resolvePath($path), $disks);
        if ($disks == 'public' || $disks == 'local') {
            if (!file_exists($completePath)) {
                if (!mkdir($completePath, 0755, true)) {
                    abort(500, 'Folder creation is failed');
                }
            }
        }
        $result = $file->store($this->resolvePath($path), $disks);
        return $result;
    }

    public function uploadFile(UploadedFile $file, $path, $meta = null, $disks = 'public', $complex = false)
    {
        $extension = $file->clientExtension();
        if ($extension == 'png' || $extension == 'jpeg') {
            return $this->uploadImageFile($file, $path, $meta, $disks, $complex);
        }
        return $this->uploadNormalFile($file, $path, $disks, $complex);
    }

    public function uploadNormalFile(UploadedFile $file, $path = '/files', $disks = 'public', $complex = false)
    {
        return $this->storeFile($file, $path, $disks, $complex);
    }

    public function uploadImageFile(UploadedFile $file, $path = '/images', $meta = null, $disks = 'public', $complex = false)
    {
        $data = $this->storeFile($file, $path, $disks, $complex);
        $filename = $this->getFileName($data);
        if (!is_null($meta)) {
            if (array_get($meta, 'modification.status') == true) {
                $image = Image::make($this->getRealPath($data, $disks));
                $width = array_get($meta, 'modification.width', null);
                $height = array_get($meta, 'modification.height', null);
                $type = array_get($meta, 'modification.type', 'fit');

                if (!is_null($width) && !is_null($height)) {
                    if ($type == 'crop') {
                        $image->crop($width, $height);
                    }
                    if ($type == 'resize') {
                        $image->resize($width, $height);
                    }
                    if ($type == 'fit') {
                        $image->fit($width, $height);
                    }
                }

                if ($disks == 'public') {
                    $image->save($this->getRealPath($data, $disks));
                } else if ($disks == 'local') {
                    $image->save($this->getRealPath($data, $disks));
                } else {
                    \Storage::put($data, $image->stream());
                }
            }
        }
        if($complex){
            $image = Image::make(\Storage::get($data));
            $preloading = $image->fit(20)->encode('data-url')->encoded;

            $thumbnail = \Storage::put($path.'/thumb_'.$filename, $image->fit(300)->stream());

            $thumbnail = $path.'/thumb_'.$filename;
            $filename = $data;
            $data = new \StdClass;
            $data->original = $filename;
            $data->thumbnail = $thumbnail;
            $data->preloading = $preloading;
            $data = json_encode($data);
        }
        // $result = $this->getRealPath($data, $disks);
        return $data;
    }
}
