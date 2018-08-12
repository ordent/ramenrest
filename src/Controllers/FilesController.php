<?php
namespace Ordent\RamenRest\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Ordent\RamenRest\Response\RestResponse;
use Ordent\RamenRest\Model\RestModelTrait;

class FilesController extends RestController
{
    use RestModelTrait;
    
    public function uploadFiles(Request $request, $rules = null){
        if(is_null($rules)){
            $rules = $request->input('rules', 'required|file|max:1048576');
        }
        $failed = $request->input('failed', 'files is either not correct or theres some problem with the connection');
        $location = $request->input('location', '/files');

        $validator = \Validator::make($request->all(), [
            "files" => $rules
        ]);

        if($validator->fails()){
            return $this->response->error(422, $validator->errors()->all(),$validator->errors());
        }
        
        $f = $request->file('files');
        $input = $request->input('files');
        $files = null;
        if(is_null($f) && !is_null($input)){
            $files = $input;
        }else if(is_null($input) && !is_null($f)){
            $files = $f;
        }else if(is_array($f) && is_array($input)){
            $files = array_merge($f, $input);
        }else if(is_array($f) && !is_array($input)){
            array_push($f, $input);
            $files = $f;
        }else if(!is_array($f) && is_array($input)){
            array_push($input, $f);
            $files = $input;
        }else{
            $files = [$input, $f];
        }
        // files
        $temp = $this->resolveUpload($files, 'files', $location, null);
        if(is_array($temp)){
            $results = [];
            foreach($temp as $t){
                array_push($results, $this->getFile($t));
            }
        }else{
            $results = $this->getFile($temp);
        }
        $response = new \StdClass;
        $response->data = new \StdClass;
        $response->meta = new \StdClass;
        $response->meta->rules = $rules;
        $response->meta->location = $location;
        $response->meta->status = 200;
        // $response->meta->extension = $files->getClientOriginalExtension();
        // $response->meta->size = $files->getClientSize();
        $response->data->files = $results;
        // $response->data->files = asset('/storage/')."/".$files->store('images/'.$location, "public");
        return response()->successResponse($response);
    }
}
