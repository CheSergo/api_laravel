<?php

namespace App\Modules\InformationSystems;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class InformationSystemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array {
        return [
            // Обязательные поля
            'title' => 'required|string|max:500',
            'short_title' => 'required|string|max:255',
            'owner_id' => 'required|numeric|integer',

            // Текстовые
            'description' => 'string|max:1000|nullable',
            'slug' => 'string|max:500|nullable',
            'link' => 'string|max:255|nullable',
            
            // Массивы
            'information_systems' => 'array|nullable',
            'information_systems.*' => 'integer',

            "documents" => "array|nullable",
            "documents.*" => "integer",

            // Числовые
            'sort' => 'integer|numeric|nullable',
            'exploitation_year' => 'integer|numeric|nullable',
            
            // Даты
            'published_at' => 'date|nullable',
            'certificate_date' => 'date|nullable',

        ];
    }

    public function messages(): array {
        return [
            'title.required' => 'Полное название информационной системы не может быть пустым',
            'title.max' => 'Допустимая длина полного названия информационной системы не более 500 символов',
            
            'short_title.required' => 'Название информационной системы не может быть пустым',
            'short_title.max' => 'Допустимая длина названия информационной системы не более 255 символов',
            
            'owner_id.required' => 'Поле «Владелец ИС» не может быть пустым',
            'owner_id.integer' => 'Ошибка при выборе владельца ИС',

            'description.max' => 'Допустимая длина описания информационной системы не более 1000 символов',

            'slug.max' => 'Допустимая длина символьного кода не более 500 символов',

            'link.max' => 'Допустимая длина ссылки не более 255 символов',

            'sort.numeric' => 'Поле «Сортировка» должно быть числом',

            'exploitation_year.numeric' => 'Поле «Год ввода в эксплуатацию» должно быть числом',

            'published_at.date' => 'Введите дату публикации',

            'certificate_date.date' => 'Введите дату выдачи сертификата',

            'information_systems.array' => 'Ошибка формата related_information_systems',
            'information_systems.*.numeric' => 'Ошибка формата related_information_systems',

            'documents.array' => 'Ошибка формата redirect',
            'documents.*.numeric' => 'Ошибка формата redirect',

        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
