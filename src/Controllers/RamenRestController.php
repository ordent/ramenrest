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

/**
 * RamenRestController class
 * 
 * Basic extendable object for Rest Controller it shall be used as your template for creating REST controllers.
 * @copyright 2018 Orde Digital Intelektual (ORDENT)
 * @author Dimas Satrio <dimassrio@gmail.com>
 * @package Ordent\RamenRest\Controllers
 */
class RamenRestController extends Controller
{
    use RamenRestControllerTrait;
    
    protected $routes = [];
    protected $model = "\Illuminate\Database\Eloquent\Model";
    protected $uri = "/";
    protected $processor = null;
    protected $res = [];
    protected $serializer = null;
    protected $meta = [];
    protected $cursor = false;

    /**
     * _construct function
     * Setting up REST controller with a REST proccessor and Model
     * @param Ordent\RamenRest\Processor\RestProcessor $processor
     * @param Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(RestProcessor $processor = null, Model $model = null)
    {
        // Inject Processor
        if(!is_null($processor)){
            $this->processor = $processor;
        }else{
            $this->processor = new RestProcessor;
        }
        // Setup model via injected constructor or basic REST / laravel model.
        $this->ramenSetModel($model);
    }
}
