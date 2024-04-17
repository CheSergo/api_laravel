<?php

namespace App\Modules\Departments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class DepartmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string|max:500',
            
            // Текстовые
            'credentials' => 'array|nullable',
            'servicies' => 'array|nullable',
            'phone' => 'string|max:255|nullable',
            'email' => 'string|max:255|nullable',
            'fax' => 'string|max:255|nullable',
            'address' => 'string|nullable',

            // Числовые
            'sort' => 'integer|nullable',
            'parent_id' => 'integer|nullable',
            'type_id' => 'integer|nullable',

            // Даты
            'published_at' => 'date|nullable',

            // Массивы
            'redirect' => 'array|nullable',
            "redirect.*.type" => "string|filled",
            "redirect.*.link" => "required_with:redirect.*.type|string|filled",

        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название не может быть пустым',
            'title.string' => 'Название должно быть строкой',
            'title.max' => 'Допустимая длина названия не более 500 символов',

            'credentials.array' => 'Ошибка формата credentials',
            'servicies.array' => 'Ошибка формата servicies',

            'phone.string' => 'Телефон должен быть строкой',
            'phone.max' => 'Длина номера телефона не более 255 символов',

            'email.string' => 'Email должен быть строкой',
            'email.max' => 'Длина Email не более 255 символов',

            'fax.string' => 'Fax должен быть строкой',
            'fax.max' => 'Длина fax не более 255 символов',
            
            'address.string' => 'Адрес должен быть строкой',

            'sort.integer' => 'Сортировка должна быть числовой',
            'parent_id.integer' => 'Ошибка формата родительского id',
            'type_id' => 'Ошибка формата типа',

            'published_at.date' => 'Дата публикации должна быть в формате даты',

            'redirect.array' => 'Ошибка формата redirect',
            "redirect.*.type" => "Ошибка формата redirect->type",
            "redirect.*.link" => "Ошибка формата redirect->link",
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
