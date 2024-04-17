<?php

namespace App\Modules\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class DocumentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:1000',
            'numb' => 'string|max:255|nullable',
            'date' => 'date|nullable',
            'published_at' => 'date',
            'type_id.value' => 'integer',
            'article_id' => 'integer',
            'section_id' => 'integer',

            'attached_documents' => 'array|nullable',

        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назваение документа не может быть пустым',
            'title.max' => 'Допустимая длина названия документа не более 1000 символов',
            'numb.max' => 'Допустимая длина номера документа не более 255 символов',
            'date.date' => 'Введите дату документа',
            'published_at.date' => 'Введите дату публикации',
            'type_id.value.integer' => 'Ошибка при выборе типа документа',
            'article_id.integer' => 'Ошибка при выборе новости',
            'section_id.integer' => 'Ошибка при выборе раздела',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
