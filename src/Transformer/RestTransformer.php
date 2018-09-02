<?php
namespace Ordent\RamenRest\Transformer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\ParamBag;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class RestTransformer extends TransformerAbstract
{
    public function transform(Model $model)
    {
        $result = array_merge($model->attributesToArray(), $model->relationsToArray());
        return $result;
    }

    protected function paginateCollection($model, ParamBag $params, RestTransformer $transformer)
    {
        [$limit, $page, $orderBy, $orderDir] = [25, 1, 'created_at', 'desc'];
        list($limit, $page) = $params->get('limit');
        if (is_null($limit)) {
            [$limit, $page] = [25, 1];
        }
        list($orderBy, $orderDir) = $params->get('order');
        if (is_null($orderBy)) {
            [$orderBy, $orderDir] = ['created_at', 'desc'];
        }
        if (($page * $limit) - $limit > 0) {
            $model = $model->offset(($page * $limit) - $limit);
        }
        $paginator = new LengthAwarePaginator($model->take($limit)->get(), $model->count(), $limit, $page);
        $resource = null;
        if (count($paginator->getCollection()) > 0) {
            $resource = new Collection($paginator->getCollection(), $transformer);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }
        return $resource;
    }
}
