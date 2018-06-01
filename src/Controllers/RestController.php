<?php

namespace Ordent\RamenRest\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Ordent\RamenRest\Processor\RestProcessor;
use Ordent\RamenRest\Requests\RestRequestFactory;
use Ordent\RamenRest\Requests\RestRequest;
use Ordent\RamenRest\Response\RestResponse;
use Illuminate\Validation\ValidationException;
use ReflectionClass;

class RestController extends Controller
{
    protected $routes = [];
    protected $model = "\Illuminate\Database\Eloquent\Model";
    protected $uri = "/";
    protected $processor = null;
    protected $res = [];
    protected $serializer = null;
    protected $meta = [];
    protected $cursor = false;
    public function __construct(RestProcessor $processor, Model $model = null)
    {
        // Inject Response
        // Inject Processor
        $this->processor = $processor;
        // Inject model
        if(!is_null($model)){
            $this->setModel($model);
        }else{
            $this->setModel($this->model);
        }
    }

    protected function setModel($model){
        if (!$this->model instanceof Model && is_string($this->model)) {
            $class = new ReflectionClass($this->model);
            if (!$class->isAbstract()) {
                $this->model = new $this->model();
                if (!is_null($this->model)) {
                    $this->processor->setModel($this->model);
                }
            }
        }

        if($model instanceof Model){
            $this->processor->setModel($this->model);            
        }
    }

    public function getCollection(Request $request)
    {

        return response()->successResponse(
            $this->processor->getCollectionStandard(
                $request, null, null, null, $this->res, $this->serializer, $this->meta));
    }

    public function getItem(Request $request, $id)
    {
        // return first id it found or not found http exception as a json
        return response()->successResponse(
            $this->processor->getItemStandard(
                $request, $id, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    

    public function postItem(Request $request, $validate = true)
    {
        // validate the request first, rules fetched from model get rules method
        $request = $this->parseValidate($validate, "store");
        // return newly created item
        if($request instanceof \Illuminate\Http\JsonResponse){
            return $request;
        }
        return response()->createdResponse(
            $this->processor->postItemStandard(
                $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }
    public function putItem($id, Request $request, $validate = true)
    {
        $request = $this->parseValidate($validate, "update");
        return response()->successResponse(
            $this->processor->putItemStandard(
                $id, $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    public function deleteItem($id, Request $request, $validate = true)
    {
        $request = $this->parseValidate($validate, "delete");
        return response()->noContentResponse(
            $this->processor->deleteItemStandard($id, $request, null, null));
    }
    
    public function postCollection(Request $request, $validate = true)
    {
        $request = $this->parseValidate($validate, "store");
        return response()->createdResponse(
            $this->processor->postItemCollection(
                $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    protected function parseValidate($validate = true, $type = "store"){
        if($validate){
            try {
                $request = RestRequestFactory::createRequest($this->model, $type);
            } catch (ValidationException $e) {
                return response()->exceptionResponse($e);
            }
            return $request;
        }
        return $request;
    }

    
}
