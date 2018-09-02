<?php 
namespace Ordent\RamenRest\Model;
use Illuminate\Database\Eloquent\Model;

class FileModel extends Model{
    use RestModelTrait;
    protected $table = 'files';
    protected $fillable = [
        'files',
        'caption'
    ];

    protected $rules = [
        'files' => 'required|files'
    ];

    public function setFilesAttribute($value){
        $this->attributes['files'] = $this->uploadFile($value, 'files', null, '/files', 'public', null);
    }

    public function getFilesAttribute(){
        return $this->getFile($this->attributes['files']);
    }

}