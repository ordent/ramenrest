<?php
namespace Ordent\RamenRest\Controllers;

use Illuminate\Http\Request;

class UserController extends RestController{
  protected $model = "\App\User";
  protected $uri = "/users/";
}