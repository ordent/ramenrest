<?php
namespace Ordent\RamenRest\Controllers;
use Illuminate\Http\Request;

class FilesController extends RestController
{

    protected $model = '\Ordent\RamenRest\Model\FileModel';
    protected $url = '/files';

    // uploading files without model

    public function modellessUpload(Request $request){
        // default rules, override it via ('rules')
        $rules = $request->input('rules', 'required|file|max:1048576');
        // default failed error message, override it via ('failed')
        $failed = $request->input('failed', 'files is either not correct or theres some problem with the connection');
        // default location, override it via ('location')
        $location = $request->input('location', 'files');

        $validator = \Validator::make($request->all(), [
            "files" => $rules
        ]);

        if($validator->fails()){
            return $this->response->error(422, $validator->errors()->all(),$validator->errors());
        }
        
        $files = $request->file('files');
        $meta = [
            'rules' => $rules,
            'location' => $location,
            'status_code' => 200,
            'extension' => $files->getClientOriginalExtension(),
            'size'=>$files->getClientSize()
        ];
        // $response = new \StdClass;
        // $response->data = new \StdClass;
        
        // $meta->rules = $rules;
        // $meta->location = $location;
        // $meta->status = 200;
        // $meta->extension = $files->getClientOriginalExtension();
        // $meta->size = $files->getClientSize();
        // // $response->data->files = asset('/storage/')."/".$files->store('images/'.$location, "public");
        // $response->data->files = $this->model->uploadFile($files, 'files', $key = null, $location, 'public', $meta = null);
        $result = array_wrap($this->model->resolveUpload($files, 'files', $location, 'public', $meta));
        
        return $this->processor->wrapModel($result, null, null, $meta, null, null, null);
        // return response()->successResponse($response);
    }

}
