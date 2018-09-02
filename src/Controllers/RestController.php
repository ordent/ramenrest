<?php

namespace Ordent\RamenRest\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Ordent\RamenRest\Processor\RestProcessor;
use Ordent\RamenRest\Requests\RestRequestFactory;
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
    use RestControllerTrait;
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
        if (!is_null($model)) {
            $this->setModel($model);
        } else {
            $this->setModel($this->model);
        }
    }
}
