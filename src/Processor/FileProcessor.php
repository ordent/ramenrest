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
            return storage_path() . '/app/public' . $path;
        } else if ($disks == 'local') {
            return storage_path() . '/app' . $path;
        } else {
            return \Storage::url($path);
        }
    }

    public function getFileName($filePath)
    {
        $temp = explode($filePath, '/');
        return $temp[count($temp) - 1];
    }

    public function storeFile(UploadedFile $file, $path = '/uploads', $disks = 'public')
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

    public function uploadFile(UploadedFile $file, $path, $meta = null, $disks = 'public')
    {
        $extension = $file->clientExtension();
        if ($extension == 'png' || $extension == 'jpeg') {
            return $this->uploadImageFile($file, $path, $meta, $disks);
        }
        return $this->uploadNormalFile($file, $path, $disks);
    }

    public function uploadNormalFile(UploadedFile $file, $path = '/files', $disks = 'public')
    {
        return $this->storeFile($file, $path, $disks);
    }

    public function uploadImageFile(UploadedFile $file, $path = '/images', $meta = null, $disks = 'public')
    {
        $data = $this->storeFile($file, $path, $disks);
        $filename = $this->getFileName($data);
        if (!is_null($meta)) {
            if (array_get($meta, 'modification.status') == true) {
                $image = Image::make($this->getRealPath($path, $disks));
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
                    \Storage::put($this->getRealPath($data, $disks), $image->stream());
                }
            }
        }
        // $result = $this->getRealPath($data, $disks);
        return $data;
    }
}
