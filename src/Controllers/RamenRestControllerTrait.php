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

/**
 * RamenRestControllerTrait trait
 * 
 * We are setting up the function so any class can use it to mirroring the REST controller.
 * 
 * @copyright 2018 Orde Digital Intelektual (ORDENT)
 * @author Dimas Satrio <dimassrio@gmail.com>
 * @package Ordent\RamenRest\Controllers
 */
trait RamenRestControllerTrait
{
    /**
     * ramenSetModel
     * 
     * setting up Model via parameter or it will use basic REST / Laravel model if inserted null 
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function ramenSetModel($model = null)
    {
        // check if $model is null and if true insert blank model to it
        if(is_null($model)){
            $model = $this->model;
        }

        // check if $model is not null and if it's already instantiated, it will instantiate if the $model is a string.
        if (!$model instanceof Model && is_string($model)) {
            $class = new ReflectionClass($model);
            if (!$class->isAbstract()) {
                $model = new $model();
                if (is_null($model)) {
                    abort(500, 'Model is failed to set since its not instantiable');
                }
            }
        }
        // ask the processor to do it in the end.
        $this->processor->setModel($model);
    }
    /**
     * ramenSetSerializer function
     *
     * @param League\Fractal\Serializer\DataArraySerializer $serializer
     * 
     * Serializer properties will gonna be used when you are transforming the data into json.
     * 
     * @return void
     */
    public function ramenSetSerializer($serializer){
        // serialize data
        $this->serializer = $serializer;
    }
    
    /**
     * ramenGetCollection function
     *
     * Will get collection of resources, you can manipulate the resources via request.
     * 
     * @param Illuminate\Http\Request $request
     * @return  Illuminate\Http\JsonResponse
     */
    public function ramenGetCollection(Request $request)
    {
        // return collection of model
        return response()->successResponse(
            $this->processor->getCollectionStandard(
                $request, null, null, null, $this->res, $this->serializer, $this->meta));
    }

    /**
     * ramenGetItem function
     *
     * Will get item of resources, you can manipulate the resources via request.
     * 
     * @param Illuminate\Http\Request $request
     * @param mixed $id
     * @return Illuminate\Http\JsonResponse
     */
    public function ramenGetItem(Request $request, $id)
    {
        // return first id (or slug) it found or not found http exception as a json
        return response()->successResponse(
            $this->processor->getItemStandard(
                $request, $id, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    /**
     * ramenPostItem function
     *
     * Will post of resources, you can manipulate the resources via request.
     * 
     * @param Illuminate\Http\Request $request
     * @param bool $validate
     * use this parameter to trigger or (not) the validation, you can skip the validation process if you want to add your own validation process.
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function ramenPostItem(Request $request, $validate = true)
    {
        // validate the request first, rules fetched from model get rules method
        $request = $this->ramenParseValidate($validate, "store");
        if ($request instanceof \Illuminate\Http\JsonResponse) {
            return $request;
        }
        // return newly created item
        return response()->createdResponse(
            $this->processor->postItemStandard(
                $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }
    /**
     * ramenPutItem function
     *
     * Will change the content of resources, you can manipulate the resources via request.
     * 
     * @param mixed $id
     * @param Illuminate\Http\Request $request
     * @param bool $validate
     * use this parameter to trigger or (not) the validation, you can skip the validation process if you want to add your own validation process.
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function ramenPutItem($id, Request $request, $validate = true)
    {
        $request = $this->ramenParseValidate($validate, "update");
        return response()->successResponse(
            $this->processor->putItemStandard(
                $id, $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    /**
     * ramenDeleteItem function
     *
     * Will delete the content of resources, you can manipulate the resources via request. (SOFT or NOT)
     * 
     * @param mixed $id
     * @param Illuminate\Http\Request $request
     * @param bool $validate
     * use this parameter to trigger or (not) the validation, you can skip the validation process if you want to add your own validation process.
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function ramenDeleteItem($id, Request $request, $validate = true)
    {
        $request = $this->ramenParseValidate($validate, "delete");
        return response()->noContentResponse(
            $this->processor->deleteItemStandard($id, $request, null, null));
    }

    /**
     * ramenPostCollection function
     * 
     * Will add a collection of resources, you can manipulate it via request
     * 
     * @param mixed $id
     * @param Illuminate\Http\Request $request
     * @param bool $validate
     * use this parameter to trigger or (not) the validation, you can skip the validation process if you want to add your own validation process.
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function ramenPostCollection(Request $request, $validate = true)
    {
        $request = $this->ramenParseValidate($validate, "store");
        return response()->createdResponse(
            $this->processor->postItemCollection(
                $request, null, null, null, $this->cursor, $this->serializer, $this->meta));
    }

    /**
     * ramenParseValidate function
     *
     * Will generate request and validate it based on inserted type.
     * 
     * @param bool $validate
     * @param string $type (store, update, delete)
     * @return Ordent\RamenRest\Requests\RestRequest
     */
    protected function ramenParseValidate($validate = true, $type = "store")
    {
        if ($validate) {
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
