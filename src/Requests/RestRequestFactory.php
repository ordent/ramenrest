<?php
namespace Ordent\RamenRest\Requests;
use Illuminate\Database\Eloquent\Model;

class RestRequestFactory{
  public static function createRequest(Model $model, $key){
        $request =  \App::makeWith('Ordent\RamenRest\Requests\RestRequest', ["model"=>$model, "key"=>$key]);
        return $request;
    }
}