<?php

namespace Ordent\RamenRest\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class RestRequest extends FormRequest
{
    protected $model = null;
    protected $key = "store";

    public function __construct(Model $model, $key)
    {
        $this->model = $model;
        $this->key = $key;
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!is_null($this->model)) {
            if (method_exists($this->model, "getAuthorization")) {
                return $this->model->getAuthorization($this->key);
            }
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (!is_null($this->model)) {
            if (method_exists($this->model, "getRules")) {
                return $this->model->getRules($this->key);
            }
        }
        return [];
    }

    public function messages()
    {
        if (!is_null($this->model)) {
            if (method_exists($this->model, "getMessages")) {
                return $this->model->getMessages($this->key);
            }
        }
        return [];
    }
}
