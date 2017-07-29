<?php
namespace Ordent\RamenRest\Transformer;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;

class RestTransformer extends TransformerAbstract
{
  public function transform(Model $model){
    $result = [
      "id" => (int) $model->id
    ];

    $fields = [];
    if(count($model->visible)>0){
      $fields = $model->getVisible();
    }else{
      foreach($model->getAttributes() as $i => $attributes){
        if(!in_array($i, $model->getHidden())){
          array_push($fields, $i);
        }
      }
    }
    
    foreach($fields as $field){
      $result[$field] = $model->$field;
    }
    
    return $result;
  }
}
