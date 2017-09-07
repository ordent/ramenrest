<?php
namespace Ordent\RamenRest\Processor;

use Illuminate\Http\Request;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Ordent\RamenRest\Transformer\RestTransformer;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ordent\RamenRest\Processor\RestEloquentRepository;

class RestProcessor
{
    protected $model = null;
    protected $repository = null;
    protected $transformer = null;
    protected $manager = null;

    public function __construct(RestEloquentRepository $repository, Manager $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    public function setModel(Model $model)
    {
        // set model
        $this->model = $model;
        // set model from repository
        $this->repository->setModel($model);
        $schema = \Schema::getColumnListing($model->getTable());
        $forget = [];
        foreach ($model->getHidden() as $key => $value) {
          array_push($forget, array_search($value, $schema));
        }
        $available = array_except($schema, $forget);
        $model->setVisible($available);
        // set transformer
        if (method_exists($this->model, "getTransformer")) {
            $this->transformer = $this->model->getTransformer();
        } else {
            $this->transformer = new RestTransformer;
        }
    }

    public function getItemStandard(Request $request, $id)
    {
        // parse request relation
        $this->parseRelation($request);
        // return the data via response class
        try{
            return $this->getItemStandardResult($this->repository->getItem($id));            
        }catch(ModelNotFoundException $e){
            abort(404);
        }
    }
    
    public function deleteItemStandard($id, Request $request)
    {
        // get parameter from request (ex : soft)
        $parameters = $this->getRequestParameters($request);
        // delete the information
        return $this->repository->deleteItem($id, $parameters);
    }
    
    public function postItemStandard(Request $request)
    {
        // get parameter from request
        $parameters = $this->getRequestParameters($request);
        // parse relation
        $this->parseRelation($request);
        // return the data via response class
        return $this->getItemStandardResult($this->repository->postItem($parameters));
    }

    public function putItemStandard($id, Request $request)
    {
        // get parameter from request        
        $parameters = $this->getRequestParameters($request);
        // parse relation
        $this->parseRelation($request);
        // return the data via response class 
        return $this->getItemStandardResult($this->repository->putItem($id, $parameters));
    }

    private function getItemStandardResult($model){
        $this->manager->setSerializer(new DataArraySerializer);
        $resource = new Item($model, $this->transformer);        
        $result = $this->manager->createData($resource)->toArray();
        return $result;
    }

    private function getCollectionStandardResult($model, $limit){
        $this->manager->setSerializer(new DataArraySerializer);
        //paginate
        $paginator = $model->paginate($limit);
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));    
        $result = $this->manager->createData($resource)->toArray();
        return $result;
    }

    private function parseRelation($request){
        if (!is_null($request->query("relation"))) {
            $this->manager->parseIncludes($request->query("relation"));
        }
        if (!is_null($request->query("with"))) {
            $this->manager->parseIncludes($request->query("with"));
        }
    }

    public function getCollectionStandard(Request $request)
    {
        $limit = $request->query('limit', 25);
        $fields = array_except($request->query(), ['limit', 'relation', 'page', 'orderBy', 'soft']);
        $soft = $request->query('soft', false);

        if ($soft) {
            $model->withTrashed();
        }
        
        $this->parseRelation($request);

        return $this->getCollectionStandardResult($this->repository->getCollection($fields, $request->query('orderBy')), $limit);
    }

    private function getRequestParameters(Request $request)
    {
        $parameters = [];
        if (count($request->json()->all())>0) {
            $parameters = $request->json()->all();
        } else {
            $parameters = $request->all();
        }
        return $parameters;
    }
}
