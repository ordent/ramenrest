<?php
namespace Ordent\RamenRest\Transformer;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;

class RestTransformer extends TransformerAbstract
{
  public function transform(Model $model){
    $result = array_merge($model->attributesToArray(), $model->relationsToArray());
    return $result;
  }
}
