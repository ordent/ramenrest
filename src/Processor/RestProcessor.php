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
use Illuminate\Pagination\LengthAwarePaginator;
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
            if($request->only('cursor') == 'true' || $request->only('cursor') == []){
                return $this->getItemStandardResult($this->repository->getItem($id));
            }else{
                return $this->getItemStandardResult($this->repository->getItem($id), false);
            }
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

    private function getItemStandardResult($model, $cursor = true){
        $this->manager->setSerializer(new DataArraySerializer);
        $previous = null;
        $next = null;
        if($cursor){
            $previous = $model->where('id', '<', $model->id)->first();
            $next = $model->where('id', '>', $model->id)->first();
        }
        $resource = new Item($model, $this->transformer);   
        $resource = $resource->setMeta(['previous'=>$previous, 'next' => $next]);
        $result = $this->manager->createData($resource)->toArray();
        return $result;
    }

    private function getCollectionStandardResult($model, $limit, $random = false){
        $this->manager->setSerializer(new DataArraySerializer);
        if($random){
            $model = $model->random($model->count())->all();
        }
        //paginate
        $paginator = $model->paginate($limit);
        $collection = $paginator->getCollection();

        $queryParams = array_diff_key($_GET, array_flip(['page']));
        $paginator->appends($queryParams);

        $resource = new Collection($collection, $this->transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));    
        $result = $this->manager->createData($resource)->toArray();
        return $result;
    }

    private function getDatatablesStandardResult($model, $limit, $offset, $request, $count){
        $this->manager->setSerializer(new DataArraySerializer);
        // $model = $model->skip($offset)->take($limit);
        //paginate
        $page = ($offset / $limit) + 1;
        $filtered = $model->get()->count();
        $paginator = new LengthAwarePaginator($model->skip($offset)->take($limit)->get(), $count, $limit, $page);
        // dd($paginator->getItems());
        // dd($paginator);
        // $paginator = $model->paginate($limit);
        
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));    
        $result = $this->manager->createData($resource)->toArray();

        $result['draw'] = $request->query('draw', 0);
        $result['recordsTotal'] = $count;
        $result['recordsFiltered'] = $filtered;
        // process datatables
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

    private function getDataTableQuery($query = [], $except = []){
        $fields = array_except($query, $except);
        $temp = [];
        return $fields;
    }

    public function getCollectionStandard(Request $request)
    {
        $fields = [];
        $this->parseRelation($request);

        if(array_key_exists('datatables', $request->query())){
            $fields = $this->getDataTableQuery($request->query(), config('ramen.reserved_datatable'));
        }else{
            $fields = array_except($request->query(), config('ramen.reserved_parameter'));            
        }
        
        
        // if doesnt have datatable pointing
        if(array_key_exists('datatables', $request->query())){
            $limit = $request->query('length', 25);
            $offset = $request->query('start', 0);
            
            return $this->getDatatablesStandardResult($this->repository->getDatatables($fields), $limit, $offset, $request, $this->model->count());
        }else{
            $limit = $request->query('limit', 25);
            return $this->getCollectionStandardResult($this->repository->getCollection($fields, $request->query('orderBy')), $limit, $request->query('random', false));
        }
        
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