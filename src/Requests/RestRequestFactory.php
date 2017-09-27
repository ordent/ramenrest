<?php
namespace Ordent\RamenRest\Requests;
use Illuminate\Database\Eloquent\Model;
use Ordent\RamenRest\Requests\RestRequest;
class RestRequestFactory{
  public static function createRequest(Model $model, $key){
        // $request =  \App::makeWith('Ordent\RamenRest\Requests\RestRequest', ["model"=>$model, "key"=>$key]);
        $request = app('Ordent\RamenRest\Requests\RestRequest', ["model"=> $model, "key"=>$key]);
        
        return $request;
    }
}