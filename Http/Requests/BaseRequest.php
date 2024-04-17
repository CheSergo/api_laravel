<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class BaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // Обязательные поля
            'title' => 'required|string|max:500',
            
            // Текстовые
            'slug' => 'string|nullable|max:500',
            'code' => 'string|nullable|max:500',
            'numb' => 'string|nullable|max:255',
            'number' => 'string|nullable|max:255',
            'description' => 'string|nullable|max:1000',
            'template' => 'string|nullable|max:255',
            'controller' => 'string|nullable|max:255',
            'parameter' => 'string|nullable|max:255',
            'comment' => 'string|nullable|max:255',
            'email' => 'string|nullable|max:255',
            'address' => 'string|nullable|max:255',
            'physical_address' => 'string|nullable',
            'phone' => 'string|nullable|max:255',
            'fax' => 'string|nullable|max:255',
            'position' => 'string|nullable|max:255',
            'link' => 'string|nullable|max:500',
            'bus_gov' => 'string|max:255',
            'file' => 'string|nullable',
            'place' => 'string|nullable|max:500',
            'path' => 'string|nullable',
            'path_old' => 'string|nullable',
            'social_links' => 'string|nullable',
            'keywords' => 'string|nullable|max:255',
            'kpp' => 'string|nullable|max:255',
            'okpo' => 'string|nullable|max:255',
            'ogrn' => 'string|nullable|max:255',
            'okved' => 'string|nullable',
            'ymetrika' => 'string|nullable|max:255',
            'type' => 'string|nullable|max:255',
            'alert' => 'string|nullable|max:500',
            'privacy_policy' => 'string|nullable',
            'icon' => 'string|nullable',
            
            // Массивы
            'body' => 'array|nullable',
            'video' => 'array|nullable',

            'redirect' => 'array|nullable',
            "redirect.*.type" => "string|filled",
            "redirect.*.link" => "required_with:redirect.*.type|string|filled",

            // Числовые
            'sort' => 'integer|nullable',
            'type_id' => 'integer|nullable',
            'venue_id' => 'integer|nullable',
            'agenda_id' => 'integer|nullable',
            'parent_id' => 'integer|nullable',
            'protocol_id' => 'integer|nullable',
            'views_count' => 'integer|nullable',
            'pos_surveys' => 'integer|nullable',
            'transcript_id' => 'integer|nullable',

            // Даты
            'date' => 'date',
            'published_at' => 'date',
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
