<?php
namespace Ordent\RamenRest\Processor;

use Illuminate\Http\Request;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\Resource\NullResource;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Ordent\RamenRest\Transformer\RestTransformer;
// use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ordent\RamenRest\Processor\RestEloquentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as ECol;
use Illuminate\Support\Collection as SCol;
class RestProcessor
{
    protected $model = null;
    protected $repository = null;
    protected $transformer = null;
    protected $manager = null;
    protected $serializer = null;
    public function __construct(RestEloquentRepository $repository, RestTransformer $transformer, DataArraySerializer $serializer)
    {
        $this->repository = $repository;
        $this->manager = app('RestManager');
        $this->transformer = $transformer;
        $this->serializer = $serializer;
        $this->manager->setSerializer($this->serializer);
    }

    public function getItemStandard(Request $request, $id, $pre = null, $intermediate = null, $post = null, $cursor = false, $serializer = null, $meta = [], $break = false){
        if(!is_null($pre)){
            $request = $pre($request);
        }
        $this->parseRelation($request);
        try{
            $result = $this->repository->getItem($id);
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
        if(!is_null($intermediate)){
            $result = $intermediate($result);
        }

        $defaultCursor = ($request->only('cursor') == 'true' || $request->only('cursor') == [] || $cursor);
        if($break){
            return array($result, $defaultCursor, $serializer, $meta, $post);
        }
        return $this->getItemStandardResult($result, $defaultCursor, $serializer, $meta, $post);
        
    }
    
    public function deleteItemStandard($id, Request $request, $pre = null, $post = null)
    {
        if(!is_null($pre)){
            $request = $pre($request);
        }
        // get parameter from request (ex : soft)
        $parameters = $this->getRequestParameters($request);
        // delete the information
        $result = $this->repository->deleteItem($id, $parameters);
        if(!is_null($post)){
            $result = $post($result);
        }
        return $result;
    }
    
    public function postItemStandard(Request $request, $pre = null, $intermediate = null, $post = null, $cursor = false, $serializer = null, $meta = [], $break = false)
    {
        if(!is_null($pre)){
            $request = $pre($request);
        }
        // get parameter from request
        $parameters = $this->getRequestParameters($request);
        // parse relation
        $this->parseRelation($request);
        // return the data via response class
        $result = $this->repository->postItem($parameters);
        if(!is_null($intermediate)){
            $result = $intermediate($result);
        }
        $defaultCursor = ($request->only('cursor') == 'true' || $request->only('cursor') == [] || array_key_exists('cursor', $request->query())|| $cursor);
        if($break){
            return array($result, $defaultCursor, $serializer, $meta, $post);
        }
        return $this->getItemStandardResult($result, $defaultCursor, $serializer, $meta, $post);
    }

    public function postItemCollection(Request $request, $pre = null, $intermediate = null, $post = null, $cursor = false, $serializer = null, $meta = [], $break = false){
        if(!is_null($pre)){
            $request = $pre($request);
        }
        $parameters = $this->getRequestParametersMultiple($request);
        $this->parseRelation($request);
        $result = [];
        foreach ($parameters as $key => $value) {
            array_push($result, $this->repository->postItem($value));
        }
        if(!is_null($intermediate)){
            $result = $intermediate($result);
        }
        $defaultCursor = ($request->only('cursor') == 'true' || $request->only('cursor') == [] || array_key_exists('cursor', $request->query())|| $cursor);
        if($break){
            return array($result, $defaultCursor, $serializer, $meta, $post);
        }
        return $this->getItemMultipleResult($result, $defaultCursor, $serializer, $meta, $post);

    }

    public function putItemStandard($id, Request $request, $pre = null, $intermediate = null, $post = null, $cursor = null, $serializer = null, $meta = [], $break = false)
    {
        if(!is_null($pre)){
            $request = $pre($request);
        }
        // get parameter from request
        $parameters = $this->getRequestParameters($request);
        // parse relation
        $this->parseRelation($request);

        $result = $this->repository->putItem($id, $parameters);
        
        // return the data via response class

        if(!is_null($intermediate)){
            $result = $intermediate($result);
        }
        $defaultCursor = ($request->only('cursor') == 'true' || $request->only('cursor') == [] || array_key_exists('cursor', $request->query())|| $cursor);
        if($break){
            return array($result, $defaultCursor, $serializer, $meta, $post);
        }
        
        return $this->getItemStandardResult($result, $defaultCursor, $serializer, $meta, $post);
    }

    public function getCollectionStandard(Request $request, $pre = null, $intermediate = null, $post = null, $reserved = [], $serializer = null, $meta = [], $break = false)
    {
        $fields = [];
        if(!is_null($pre)){
            $request = $pre($request);
        }
        $this->parseRelation($request);
        if (array_key_exists('datatables', $request->query())) {
            $limit = $request->query('length', 25);
            $offset = $request->query('start', 0);
            $fields = $this->getDataTableQuery($request->query(), array_merge(config('ramen.reserved_datatable'), $reserved));
            $result = $this->repository->getDatatables($fields);
        } else {
            $limit = $request->query('limit', 25);
            $fields = array_except($request->query(), array_merge(config('ramen.reserved_parameter'), $reserved));
            $result = $this->repository->getCollection($fields, $request->query('orderBy'));
        }
        if(!is_null($intermediate)){
            $result = $intermediate($result);
        }
        if($break){
            if (array_key_exists('datatables', $request->query())) {
                return array($result, $limit, $offset, $request, $this->model->count(), $serializer, $meta, $post);
            } else {
                return array($result, $limit, $request->query('random', false), $serializer, $meta, $post);
            }
        }
        // if doesnt have datatable pointing
        if (array_key_exists('datatables', $request->query())) {
            return $this->getDatatablesStandardResult($result, $limit, $offset, $request, $this->model->count(), $serializer, $meta, $post);
        } else {
            return $this->getCollectionStandardResult($result, $limit, $request->query('random', false), $serializer, $meta, $post);
        }
    }
    // wrapper
    public function setModel(Model $model)
    {
        // set model
        $this->model = $model;
        // set model from repository
        $this->repository->setModel($model);
        // set transformer
        $this->setTransformer($this->model);
    }

    public function getModel(){
        return $this->model;
    }

    public function getManager(){
        return $this->manager;
    }

    public function getTransformer(){
        return $this->transformer;
    }

    public function getSerializer(){
        return $this->serializer;
    }

    public function getRepository(){
        return $this->repository;
    }

    public function setTransformer(Model $model){
        if (method_exists($this->model, "getTransformer")) {
            $this->transformer = $this->resolveTransformer($this->model->getTransformer());
        }else if( !is_null($this->model->transformer) ){
            $this->transformer = $this->resolveTransformer($this->model->transformer);
        }
    }
    private function resolveTransformer($transformer = null){
        if(is_string($transformer)){
            return app($transformer);
        }else if(is_null($transformer)){
            return $this->transformer;
        }
        return $transformer;
    }
    private function resolveSerializer($serializer){
        if(is_string($serializer)){
            return app($serializer);
        }
        if(is_null($serializer)){
            return $this->serializer;
        }
        return $serializer;
    }
    public function getItemStandardResult($model, $cursor = true, $serializer = null, $meta = [], $post = null)
    {
        if(!is_null($serializer)){
            $this->manager->setSerializer($this->resolveSerializer($serializer));
        }
        $defaultMeta = [
            'status_code' => 200,
            'total' => $model->count()
        ];
        if($post){
            $model = $post($model);
        }
        if ($cursor) {
            $previous = null;
            $next = null;
            $previous = $model->where('id', '<', $model->id)->orderBy('id', 'desc')->first();
            $next = $model->where('id', '>', $model->id)->orderBy('id', 'asc')->first();
            $status_code = 200;
            $defaultMeta = array_merge($defaultMeta, ['previous'=>$previous, 'next' => $next]);
        }

        $resource = new Item($model, $this->transformer);

        $resource = $resource->setMeta(array_merge($defaultMeta, $meta));

        $result = $this->manager->createData($resource)->toArray();

        return $result;
    }

    public function getItemMultipleResult($model, $cursor = true, $serializer = null, $meta = [], $post = null)
    {
        if(!is_null($serializer)){
            $this->manager->setSerializer($this->resolveSerializer($serializer));
        }
        $defaultMeta = [];

        $resource = new Collection($model, $this->transformer);

        $resource = $resource->setMeta(array_merge($defaultMeta, $meta));

        $result = $this->manager->createData($resource)->toArray();

        return $result;
    }

    public function getCollectionStandardResult($model, $limit = 25, $random = false, $serializer = null, $meta = null, $post = null)
    {
        if(!is_null($serializer)){
            $this->manager->setSerializer($this->resolveSerializer($serializer));
        }
        $defaultMeta = [
            'status_code' => 200,
            'message' => 'collection successfully returned'
        ];

        if ($random) {
            $model = $model->random($model->count())->all();
        }
        //paginate
        $paginator = $model->paginate($limit);
        $collection = $paginator->getCollection();
        if($post){
            $collection = $post($collection);
        }
        $queryParams = array_diff_key($_GET, array_flip(['page']));
        
        $paginator->appends($queryParams);
        $resource = new Collection($collection, $this->transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        $resource->setMeta(array_merge($defaultMeta, $meta));
        $result = $this->manager->createData($resource)->toArray();
        return $result;
    }

    private function getDatatablesStandardResult($model, $limit = 25, $offset = 0, $request, $count, $serializer = null, $meta = null, $post = null)
    {
        if(!is_null($serializer)){
            $this->manager->setSerializer($this->resolveSerializer($serializer));
        }
        $defaultMeta = [];
        //paginate
        $page = ($offset / $limit) + 1;
        $filtered = $model->get()->count();
        $paginator = new LengthAwarePaginator($model->skip($offset)->take($limit)->get(), $count, $limit, $page);
        $collection = $paginator->getCollection();
        if($post){
            $collection = $post($collection);
        }
        $resource = new Collection($collection, $this->transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        $resource->setMeta(array_merge($defaultMeta, $meta));
        $result = $this->manager->createData($resource)->toArray();
        $defaultData = [
            'draw' => $request->query('draw', 0),
            'recordsTotal' => $count,
            'recordsFiltered' => $filtered
        ];
        $result = array_merge($result, $defaultData);
        // process datatables
        return $result;
    }
    // utilities
    private function getRequestParameters(Request $request)
    {
        $parameters = [];
        if (count($request->json()->all())>0) {
            $parameters = $request->json()->all();
        } else {
            $parameters = [];
            // $parameters = $request->all();
            // check if both file and input exists
            foreach($request->input() as $k => $i){
                if(array_key_exists($k, $request->file())){
                    $temp = [];
                    if(is_array($i) && is_array($request->file($k))){
                        $temp = array_merge($i, $request->file($k));
                    }else if(is_array($i) && !is_array($request->file($k))){
                        $temp = $i;
                        array_push($temp, $request->file($k));
                    }else if(!is_array($i) && is_array($request->file($k))){
                        $temp = $request->file($k);
                        array_push($temp, $i);
                    }else{
                        array_push($temp, $i);
                        array_push($temp, $request->file($k));
                    }
                    $replace = [
                        $k => $temp
                    ];
                }else{
                    $replace = [
                        $k => $i
                    ];
                }
                $parameters = array_merge($parameters, $replace);
            }
            foreach($request->file() as $k => $f){
                if(!array_key_exists($k, $request->input())){
                    $replace = [
                        $k => $f
                    ];
                    $parameters = array_merge($parameters, $replace);
                }
            }
        }
        return $parameters;
    }

    private function getRequestParametersMultiple(Request $request)
    {
        $parameters = [];
        if (count($request->json()->all())>0) {
            $data = $request->json()->all();
        } else {
            $data = $request->all();
        }
        if(is_array($data)){
            if(is_array(array_first($data))){
                $row = count(array_first($data));
                for ($i=0; $i < $row; $i++) { 
                    $d = [];
                    foreach ($data as $key => $value) {
                        $d[$key] = $value[$i];
                    }
                    array_push($parameters, $d);
                }
            }
        }
        return $parameters;
    }

    private function parseRelation($request)
    {
        if (!is_null($request->query("relation"))) {
            $this->manager->parseIncludes($request->query("relation"));
        }
        if (!is_null($request->query("with"))) {
            $this->manager->parseIncludes($request->query("with"));
        }
    }

    private function getDataTableQuery($query = [], $except = [])
    {
        $fields = array_except($query, $except);
        $temp = [];
        return $fields;
    }

    public function wrapModel($result, $serializer, $transformer, $meta = [], $paginator, $request = null, $post = null){
        $this->manager->setSerializer($this->resolveSerializer($serializer));
        if(!is_null($request)){
            $this->parseRelation($request);
        }
        if($result instanceof ECol || $result instanceof SCol){
            $resource = new Collection($result, $this->resolveTransformer($transformer));
            if(!is_null($paginator)){
                $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
            }
        }else if($result instanceof Model){
            $resource = new Item($result, $this->resolveTransformer($transformer));
        }else if(is_null($result)){
            $resource = new NullResource();
        }else{
            $resource = new Item($result, function($data){
                return $data;
            });
        }
        if(!is_null($meta)){
            if(is_object($meta)){
                $meta = (array) $meta;
            }
            $resource->setMeta($meta);
        }
        $results = $this->manager->createData($resource)->toArray();
        if(is_array($results['data'])){
            if(count($results['data']) == 0){
                $results['data'] = null;
            }
        }
        if(!is_null($post)){
            $results = $post($results);
        }
        return $results;
    }
}
