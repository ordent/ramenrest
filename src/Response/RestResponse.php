<?php
namespace Ordent\RamenRest\Response;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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
  // exception response
    public function exceptionResponse($exception)
    {
        $result = null;
        $status = 0;
        $result = null;
        if ($exception instanceof \ErrorException) {
            $status = 500;            
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage());
            } else {
                $result = $this->errorException($status, "Error Exception");
            }
        }
        
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            $status = 404;        
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage());
            } else {
                $result = $this->errorException($status, "Entity not found");
            }
        }
        // 500
        if ($exception instanceof QueryException) {
            $status = 500;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage());
            } else {
                $result = $this->errorException($status, "Database Error, please notify administrator");
            }
        }
        if ($exception instanceof ValidationException) {
            $status = 422;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage(), $exception->validator->getMessageBag()->all());
            } else {
                $result = $this->errorException($status, "You failed the validation test.");
            }
        }

        if ($exception instanceof MassAssignmentException) {
            $status = 422;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage(), $exception->validator->getMessageBag()->all());
            } else {
                $result = $this->errorException($status, "Assigment failed, please check if the properties is properly allowed.");
            }
        }

        if ($exception instanceof BadMethodCallException) {
            $status = 500;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage());
            } else {
                $result = $this->errorException($status, "App can't find the method that you use.");
            }
        }
        if($exception instanceof MethodNotAllowedHttpException){
            $status = 405;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage());
            } else {
                $result = $this->errorException($status, " Method is not allowed.");
            }
        }
        

        if ($exception instanceof \Exception && $result == null) {
            
            $status = 500;
            if ($exception->getMessage() != "") {
                $result = $this->errorException($status, $exception->getMessage(), $exception->getFile().":".$exception->getLine(), $exception->getTrace());
            } else {
                $result = $this->errorException($status, "Assignment failed, please check if the properties is properly allowed.");
            }
        }
        
        return $result;
    }

    private function errorException($status = 500, $message = null, $detail = null, $exception = null)
    {
        $result = new \StdClass;
        $result->meta = new \StdClass;
        $result->data = [];
        $result->meta->status_code = $status;
        if (!is_null($message)) {
            $result->meta->message = $message;
        }
        if (!is_null($detail)) {
            $result->meta->detail = $detail;
        }
        if (!is_null($exception)) {
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
        // //create response content in array format
        // $content['status'] = $statusCode;
        // $content['message'] = $message;
        // if ( $detail ){
        //     $content['detail'] = $detail;
        // }
        // $data['errors'] = $content;
        // //create json response and throw it
        // response()->json($data, $statusCode)->throwResponse();
        return $this->errorException($statusCode, $message, $detail);
    }
}