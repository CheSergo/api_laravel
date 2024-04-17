<?php

namespace App\Modules\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Helpers
use Carbon\Carbon;
use App\Helpers\Api\ApiResponse;

class UserRequest extends FormRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            "name" => "required|max:191",
            "surname" => "required|max:191",
            "second_name" => "string|max:191|nullable",
            
            "email" => [
                'required',
                'string',
                'max:191',
                Rule::unique('users')->ignore($this->id),
                'regex:/^[A-Za-z0-9._%+-]+@(astrobl\.ru|astrmail\.ru)$/i'
            ],
            "phone" => "string|max:255|nullable",
            "position" => "string|max:255|nullable",

            "siteRoles" => "array",
            "siteRoles.*.id" => "integer",
            "siteRoles.*.roles" => "array",
            "siteRoles.*.roles.*" => "integer",
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно',
            'surname.required' => 'Фамилия обязательна',
            'email.required' => 'email обязателен',
            'email.max' => 'email не более 191 символа',
            'email.unique' => 'Такой email уже существует',
            'email.regex' => 'Допустимый домен для email только @astrobl.ru или @astrmail.ru',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
}
