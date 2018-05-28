<?php
namespace Ordent\RamenRest\Response;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use BadMethodCallException;
class RestResponse
{
  // success response
    public function successResponse($data, $cache = false)
    {
        if($cache){
            return response()->json($data);
        }else{
            return response()->json($data)->header('Cache-Control','max-age=0,must-revalidate');            
        }
    }

    public function createdResponse($data)
    {
        return response()->json($data, 201);
    }
    
    public function noContentResponse()
    {
        return response()->json([], 204);
    }

    public function exceptionResponse($exception){
        $status = 500;
        $result = null;
        $details = null;
        $trace = null;
        if(\App::environment() == 'local'){
            $details = $exception->getFile().":".$exception->getLine();
            $trace = $exception->getTrace();
        }
        switch ($exception) {
            case ($exception instanceof ModelNotFoundException) : 
                $result = $this->errorException(404, $this->resolveMessage($exception->getMessage(), "Either routes or entity that you want is not found."), $details, $trace);  
                break;
            case ($exception instanceof NotFoundHttpException):
                $result = $this->errorException(404, $this->resolveMessage($exception->getMessage(), "Either routes or entity that you want is not found."), $details, $trace);  
                break;
            case ($exception instanceof QueryException):
                $result = $this->errorException(500, $this->resolveMessage($exception->getMessage(), "Can't connect to database or there is something wrong with your query."), $details, $trace);  
                break;
            case ($exception instanceof ValidationException):
                $result = $this->errorException(422, $this->resolveMessage($exception->getMessage(), "Inputted data is not valid. Check the validation rules response."), $exception->validator->getMessageBag()->all());
                break;
            case ($exception instanceof MassAssignmentException):
                $result = $this->errorException(422, $this->resolveMessage($exception->getMessage(), "Inputted data is not valid, some properties cannot be assigned."), $details, $trace);  
                break;
            case ($exception instanceof MethodNotAllowedHttpException):
                $result = $this->errorException(405, $this->resolveMessage($exception->getMessage(), "Unfortunately the method is not available to access."), $details, $trace);  
                break;
            case ($exception instanceof BadMethodCallException):
                $result = $this->errorException(500, $this->resolveMessage($exception->getMessage(), "Some function is not found or there is something wrong with the call stack."), $details, $trace);  
                break;
            case ($exception instanceof HttpException):
                $result = $this->errorException($exception->getStatusCode(), $this->resolveMessage($exception->getMessage(), "Default error HTTP exception."), $details, $trace);  
                break;
            case ($exception instanceof \ErrorException):
                $result = $this->errorException(500, $this->resolveMessage($exception->getMessage(), "Default error exception, unfortunately it has not been specified in registry."), $details, $trace);
                break;
            case ($exception instanceof \Exception):
                $result = $this->errorException(500, $this->resolveMessage($exception->getMessage(), "Default error exception, unfortunately it has not been specified in registry."), $details, $trace);
                break;
            default:
                $result = $this->errorException(500, $this->resolveMessage($exception->getMessage(), "Default error exception, unfortunately it has not been specified in registry."), $details, $trace);
                break;
        }
        return $result;
    }

    private function resolveMessage($message, $default = "Default error exception"){
        if(is_null($message) || $message == ""){
            return $default;
        }
        return $message;
    }
  // exception response

    private function errorException($status = 500, $message = null, $detail = null, $exception = null)
    {
        $result = new \StdClass;
        $result->meta = new \StdClass;
        $result->data = null;
        $result->meta->status_code = $status;
        if(!is_array($detail)){
            $detail = [$detail];
        }
        if (!is_null($message)) {
            $result->meta->message = $message;
        }
        if (!is_null($detail)) {
            $result->meta->detail = $detail;
        }
        if (!is_null($exception) && \App::environment('local')) {
            $result->meta->exception = $exception;
        }
        return response()->json($result, $status);
    }

    // 400 bad request. general request error
    public function errorBadRequest($message = 'Bad request'){
        return $this->error(400, $message);
    }
    // 401 unauthorized. auth failed error
    public function errorUnauthorized($message = 'Unauthorized'){
        return $this->error(401, $message);
    }
    // 403 forbidden error
    public function errorForbidden($message = 'Forbidden'){
        return $this->error(403, $message);
    }
    // 404 not found
    public function errorNotFound($message = 'Resource not found'){
        return $this->error(404, $message);
    }
    // UNUSED FOR NOW
    // 405 method not allowed error
    public function errorMethodNotAllowed($message = 'Method Not Allowed'){
        return $this->error($message, 405);
    }
    // 422 validation error
    public function errorValidation($errors = null, $message = 'Validation failed'){
       return  $this->error(422, $message, $errors);
    }
    // 500 internal error. general system error
    public function errorInternal($message = 'Internal error'){
        return $this->error(500, $message);
    }
    //general error
    public function error($statusCode = 500, $message = null, $detail = null){
        return $this->errorException($statusCode, $message, $detail);
    }
}