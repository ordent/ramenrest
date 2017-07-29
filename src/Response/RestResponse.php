<?php
namespace Ordent\RamenRest\Response;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

class RestResponse
{
  // success response
    public function successResponse($data)
    {
        return response()->json($data);
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
        // 404
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
        return response()->json($result, $status);
    }

    private function errorException($status = 500, $message = null, $detail = null)
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
        return $result;
    }

    // 400 bad request. general request error
    public function errorBadRequest($message = 'Bad request'){
        $this->error(400, $message);
    }
    // 401 unauthorized. auth failed error
    public function errorUnauthorized($message = 'Unauthorized'){
        $this->error(401, $message);
    }
    // 403 forbidden error
    public function errorForbidden($message = 'Forbidden'){
        $this->error(403, $message);
    }
    // 404 not found
    public function errorNotFound($message = 'Resource not found'){
        $this->error(404, $message);
    }
    // UNUSED FOR NOW
    // 405 method not allowed error
    // public function errorMethodNotAllowed($message = 'Method Not Allowed'){
    //     $this->error($message, 405);
    // }
    // 422 validation error
    public function errorValidation($errors = null, $message = 'Validation failed'){
        $this->error(422, $message, $errors);
    }
    // 500 internal error. general system error
    public function errorInternal($message = 'Internal error'){
        $this->error(500, $message);
    }
    //general error
    public function error($statusCode = 500, $message = null, $detail = null){
        //create response content in array format
        $content['status'] = $statusCode;
        $content['message'] = $message;
        if ( $detail ){
            $content['detail'] = $detail;
        }
        $data['errors'] = $content;
        //create json response and throw it
        response()->json($data, $statusCode)->throwResponse();
    }
}
