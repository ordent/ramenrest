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
    public function __construct(RestEloquentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->repository->setModel($model);
        if (method_exists($this->model, "getTransformer")) {
            $this->transformer = $this->model->getTransformer();
        } else {
            $this->transformer = new RestTransformer;
        }
    }

    public function getItemStandard(Request $request, $id)
    {
        try {
            $model = $this->repository->getItem($id);
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
    
        return $this->getItemResult($model, $request);
    }
    
    public function deleteItemStandard($id, Request $request)
    {
        $parameters = $this->getRequestParameters($request);
        return $this->repository->deleteItem($id, $request);
    }
    
    public function postItemStandard(Request $request)
    {
        $parameters = $this->getRequestParameters($request);
        return $this->getItemResult($this->repository->postItem($parameters), $request);
    }

    public function putItemStandard($id, Request $request)
    {
        $parameters = $this->getRequestParameters($request);
        return $this->getItemResult($this->repository->putItem($id, $parameters), $request);
    }
    private function getItemResult($model, $request)
    {
        $resource = new Item($model, $this->transformer);
        $manager = new Manager();

        $manager->setSerializer(new DataArraySerializer());
        if (!is_null($request->query("relation"))) {
              $manager->parseIncludes($request->query("relation"));
        }
    
        $result = $manager->createData($resource)->toArray();
        
        return $result;
    }

    public function getCollectionStandard(Request $request)
    {
        $limit = $request->query('limit', 25);
        $fields = array_except($request->query(), ['limit', 'relation', 'page', 'orderBy', 'soft']);
        $soft = $request->query('soft', false);

        $model = $this->repository->getCollection($fields, $request->query('orderBy'));
        if ($soft) {
            $model->withTrashed();
        }
        //paginate
        $paginator = $model->paginate($limit);

        $collection = $paginator->getCollection();
        
        $resource = new Collection($collection, $this->transformer);
        
        $result = $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $manager = new Manager();

        $manager->setSerializer(new DataArraySerializer());
        if (!is_null($request->query("relation"))) {
            $manager->parseIncludes($request->query("relation"));
        }
        return $manager->createData($result)->toArray();
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
