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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SCol;
use League\Fractal\Resource\Collection as FCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Ordent\RamenRest\Transformer\RestTransformer;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;


class RestController extends Controller
{
    protected $routes = [];
    protected $model = "\Illuminate\Database\Eloquent\Model";
    protected $uri = "/";
    protected $processor = null;

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
        // return collection
        return response()->successResponse($this->processor->getCollectionStandard($request));
    }

    public function getItem(Request $request, $id)
    {
        // return first id it found or not found http exception as a json
        return response()->successResponse($this->processor->getItemStandard($request, $id));
    }

    public function postItem(Request $request, $validate = true)
    {
        // validate the request first, rules fetched from model get rules method
        if($validate){
            try {
                $request = RestRequestFactory::createRequest($this->model, "store");
            } catch (ValidationException $e) {
                return response()->exceptionResponse($e);
            }
        }
        // return newly created item
        return response()->createdResponse($this->processor->postItemStandard($request));
    }
    public function putItem($id, Request $request, $validate = true)
    {
        if($validate){
            try {
                $request = RestRequestFactory::createRequest($this->model, "update");
            } catch (ValidationException $e) {
                return response()->exceptionResponse($e);
            }
        }

        return response()->successResponse($this->processor->putItemStandard($id, $request));
    }

    public function deleteItem($id, Request $request, $validate = true)
    {
        if($validate){
            try {
                $request = RestRequestFactory::createRequest($this->model, "delete");
            } catch (ValidationException $e) {
                return response()->exceptionResponse($e);                
            }
        }
                return response()->noContentResponse($this->processor->deleteItemStandard($id, $request));
    }

    protected function wrapModel($result, $meta = null, $paginator = null){
        $manager = new Manager;
        $manager->setSerializer(new DataArraySerializer);

        if($result instanceof Collection || $result instanceof SCol){
            $transformer = null;
            if(!is_null($result->first())){
                $transformer = $result->first()->getTransformer();
            }
            $transformer = $this->parseTransformer($transformer);
            $resource = new FCollection($result, $transformer);
            if(!is_null($paginator)){
                $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
            }
        }else{
            $transformer = null;
            if(!is_null($transformer)){
                $transformer = $result->getTransformer();
            }
            $transformer = $this->parseTransformer($transformer);            
            $resource = new Item($result, $transformer);
        }
        if(!is_null($meta)){
            $resource->setMeta($meta);
        }
        $results = $manager->createData($resource)->toArray();
        
        return $results;
    }

    protected function parseTransformer($transformer){
        if(is_string($transformer)){
            return app($transformer);
        }else if(is_null($transformer)){
            return new RestTransformer;
        }else{
            return $transformer;
        }
    }
}
