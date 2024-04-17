<?php

namespace App\Modules\Institutions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class InstitutionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'email' => 'string|max:255|nullable',
            'address' => 'string|max:255|nullable',
            'phone' => 'string|max:255|nullable',
            'fax' => 'string|max:255|nullable',
            // 'position' => 'string|max:255|nullable',
            // 'director' => 'string|max:255|nullable',
            // 'link' => 'string|max:255|nullable',
            'bus_gov' => 'string|max:255|nullable',
            'published_at' => 'date|nullable',
            
            // Integers
            'sort' => 'integer|nullable',
            'type_id' => 'required|integer',

            // Arrays
            'body' => 'array|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение новости не может быть пустым',
            'email.string' => 'Email должен быть строкой',
            'email.max' => 'Допустимая длина поля email 255 символов',

            'address.string' => 'Адрес должен быть строкой',
            'address.max' => 'Допустимая длина адреса 255 символов',

            'phone.string' => 'Телефон должен быть строкой',
            'phone.max' => 'Допустимая длина телефона 255 символов',

            'fax.string' => 'Fax должен быть строкой',
            'fax.max' => 'Допустимая длина fax 255 символов',

            'bus_gov.string' => "Сылка на bus_gov должна быть строкой",

            'published_at.date' => 'Дата публикации должна быть в формате даты',

            'sort.integer' => 'Формат сортировки должен быть числовой',
            'type_id.integer' => 'Ошибка формата типа',
            'body.array' => 'Ошибка формата body',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
