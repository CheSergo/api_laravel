<?php

namespace App\Modules\Instructions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class InstructionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string|max:255',

            // Текстовые
            'slug' => 'string|max:255|nullable',
            'body' => 'array|nullable',
            'description' => 'string|nullable',

            // Числовые
            'sort' => 'integer|numeric|nullable',
            'parent_id' => 'integer|nullable',
            'views_count' => 'integer|nullable',
            
            // Даты
            'published_at' => 'date|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение новости не может быть пустым',
            'title.string' => 'Назваение должно быть строкой',
            'title.max' => 'Допустимая длина поля название 255 символов',

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
