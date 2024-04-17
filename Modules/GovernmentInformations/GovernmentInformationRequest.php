<?php

namespace App\Modules\GovernmentInformations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class GovernmentInformationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string|max:500',

            // Текстовые
            // 'slug' => 'string|max:500|nullable',
            
            // Массивы
            'body' => 'array|nullable',
            'redirect' => 'array|nullable',

            // Числовые
            'sort' => 'integer|numeric|nullable',
            'type_id' => 'integer|nullable',
            'views_count' => 'integer|nullable',
            
            // Даты
            'published_at' => 'date|nullable',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
