<?php

namespace App\Modules\Contests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class ContestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "title" => "required|max:255",
            "slug" => "string|max:255|nullable",
            "published_at" => "date",
            "end_at" => "required|date",
            "documents" => "array|nullable",
            "documents.*" => "integer",
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение новости не может быть пустым',
            'title.max' => 'Допустимая длина Назваения не более 255 символов',

            "published_at.date" => "Дата публикации должна быть в формате даты",

            "end_at.required" => "Дата окончания не может быть пустым",
            "end_at.date" => "Дата окончания должна быть в формате даты",

            'slug.string' => 'Slug должно быть строкой',
            'slug.max' => 'Slug должен быть не более 255 символов',

            "documents.array" => "Документы. Ошибка в формате передачи данных",
        ];
    }


    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
}