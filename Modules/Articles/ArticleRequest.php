<?php

namespace App\Modules\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class ArticleRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            "title" => "required|max:255",
            "slug" => "string|max:255",
            "video" => "array",
            "published_at" => "date",
            "pin_date" => "date",

            "poster" => "array|nullable",
            "poster.base64" => "string|nullable",
            // "poster.name" => "required_with:poster.base64|filled|string",
            "poster.filename" => "required_with:poster.base64|filled|string",
            "poster.*.id" => "integer|nullable",
            // "poster.*.delete" => "boolean",

            "gallery" => "array|nullable",
            "gallery.*.base64" => "string|nullable",
            "gallery.*.name" => "required_with:gallery.*.base64|filled|string",
            "gallery.*.filename" => "required_with:gallery.*.base64|filled|string",
            "gallery.*.id" => "integer|nullable",

            "categories" => "required|array",
            "categories.*" => "integer",

            "tags" => "array|nullable",
            // "tags.*.value" => "required_with:tags.*|filled|string",
            // "tags.*.label" => "required_with:tags.*|filled|string",

            "sources" => "array|nullable",
            "sources.*" => "integer",

            "directions" => "array|nullable",
            "directions.*" => "integer",

            "documents" => "array|nullable",
            "documents.*" => "integer",
            
            "persons" => "array|nullable",
            "persons.*" => "integer",
            // "persons.*.id" => "required_with:persons.*|filled|integer",
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение новости не может быть пустым',
            'title.max' => 'Допустимая длина Назваения не более 255 символов',
            'video.array' => 'Неверный формат поля Видео',
            "published_at.date" => "Дата публикации должна быть в формате даты",
            "pin_date.date" => "Дата приклепления должна быть в формате даты",
            "poster.array" => "Неверный формат передачи медиа постера",
            "gallery.array" => "Неверный формат передачи медиа постера",
            "gallery.*.base64.string" => "Base64 при передачи галлереди должен быть строкой",
            "gallery.*.name.string" => "name при передачи галлереди должен быть строкой",
            "gallery.*.filename.string" => "filename при передачи галлереди должен быть строкой",
            "gallery.*.id.integer" => "Id Файла галлереи должен быть в числовом формате",
            "categories.required" => "Выберите категории новостей",
            "tags.array" => "Теги. Ошибка в формате передачи данных",
            "sources.array" => "Источники. Ошибка в формате передачи данных",
            "directions.array" => "Направления деятельности. Ошибка в формате передачи данных",
            "documents.array" => "Документы. Ошибка в формате передачи данных",
            "persons.array" => "Персоны. Ошибка в формате передачи данных",
        ];
    }


    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
}
