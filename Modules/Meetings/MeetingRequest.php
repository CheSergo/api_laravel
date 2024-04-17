<?php

namespace App\Modules\Meetings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class MeetingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string',

            // Текстовые
            'place' => 'string|max:500|nullable',

            // Массивы
            'video' => 'array|nullable',
            'agenda' => 'array|nullable',
            'protocol' => 'array|nullable',
            'transcript' => 'array|nullable',
            'commissions' => 'array|nullable',

            // Числовые
            'venue_id' => 'integer|numeric|nullable',
            'type_id' => 'integer|numeric|nullable',
            
            // Даты
            'begin_time_at' => 'date|nullable',
            'published_at' => 'date|nullable',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
