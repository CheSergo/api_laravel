<?php

namespace App\Modules\Directions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class DirectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Обязательные поля
            "title" => "required|string|max:500",
            'slug' => "nullable|string|max:500",

            // Даты
            'published_at' => 'date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название не может быть пустым',
            'title.string' => 'Название должно быть строкой',
            'title.max' => 'Допустимая длина названия не более 500 символов',

            'slug.required' => 'slug не может быть пустым',
            'slug.string' => 'slug должнен быть строкой',
            'slug.max' => 'Допустимая длина slug не более 500 символов',

            'published_at.date' => 'Дата публикации должна быть в формате даты',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
