<?php

namespace App\Modules\PublicHearings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class PublicHearingRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            "title" => "required|max:500",

            "advertisement" => "array|nullable",
            "decision" => "array|nullable",

            "published_at" => "date",
            "date_start" => "required|date",
            "date_end" => "required|date",

            "sources" => "array|nullable",
            "sources.*" => "integer",

            "documents" => "array|nullable",
            "documents.*" => "integer",
            
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение публичного слушания не может быть пустым',
            'title.max' => 'Допустимая длина Назваения не более 500 символов',
            'advertisement.array' => 'Неверный формат поля Объявления',
            'decision.array' => 'Неверный формат поля Решение',
            "published_at.date" => "Дата публикации должна быть в формате даты",
            "date_start.date" => "Дата начала должна быть в формате даты",
            "date_start.required" => "Дата начала обязательно для заполнения",
            "date_end.date" => "Дата окончания должна быть в формате даты",
            "date_end.required" => "Дата окончания обязательно для заполнения",
            "tags.array" => "Теги. Ошибка в формате передачи данных",
            "sources.array" => "Источники. Ошибка в формате передачи данных",
            "documents.array" => "Документы. Ошибка в формате передачи данных",
        ];
    }


    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
}
