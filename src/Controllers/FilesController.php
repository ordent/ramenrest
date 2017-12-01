<?php
namespace Ordent\RamenRest\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Ordent\RamenRest\Response\RestResponse;

class FilesController extends Controller
{
    function __construct(RestResponse $response){
        $this->response = $response;
    }
    function uploadFiles(Request $request){
        $rules = $request->input('rules', 'required|file|max:1048576');
        $failed = $request->input('failed', 'files is either not correct or theres some problem with the connection');
        $location = $request->input('location', 'standard');
        $validator = \Validator::make($request->all(), [
            "files" => $rules
        ]);

        if($validator->fails()){
            return $this->response->error(422, $validator->errors()->all(),$validator->errors());
        }
        
        $files = $request->file('files');
        
        $response = new \StdClass;
        $response->data = new \StdClass;
        $response->meta = new \StdClass;
        $response->meta->rules = $rules;
        $response->meta->location = $location;
        $response->meta->status = 200;
        $response->meta->extension = $files->getClientOriginalExtension();
        $response->meta->size = $files->getClientSize();

        $response->data->files = asset('/storage/')."/".$files->store('images/'.$location, "public");
        return $this->response->successResponse($response);
    }
}
