<?php

namespace App\Modules\Commissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class CommissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string|max:500',
            
            // Текстовые
            'slug' => 'string|max:500',
            'description' => 'string|max:1000|nullable',
            'period_meeting' => 'string|max:255|nullable',
            'info' => 'string|max:500|nullable',

            // Даты
            'published_at' => 'date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение новости не может быть пустым',
            'title.string' => 'Название должно быть строкой',
            'title.max' => 'Допустимая длина Назваения не более 500 символов',

            'slug.string' => 'Slug должно быть строкой',
            'slug.max' => 'Slug должен быть не более 500 символов',

            'description.string' => 'Описание должно быть строкой',
            'description.max' => 'Допустимая длина описания не более 1000 символов',

            'period_meeting.string' => 'Период должно быть строкой',
            'period_meeting.max' => 'Допустимая длина Периода не более 255 символов',

            'info.string' => 'Информация должно быть строкой',
            'info.max' => 'Допустимая длина Информации не более 500 символов',

            'published_at.date' => 'Дата публикации должна быть в формате даты',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
