<?php

namespace App\Modules\Sites;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class SiteRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            "title" => "required|max:255",
            "slug" => "string|max:255|nullable",
            "published_at" => "date|nullable",
            "domain" => "required|string|max:255",
            "path" => "required|string",
            "path_old" => "string|nullable",
            "description" => "string|max:255|nullable",
            "keywords" => "string|max:255|nullable",
            "address" => "string|nullable",
            "physical_address" => "string|nullable",
            "fax" => "string|max:255|nullable",
            "phone" => "string|max:255|nullable",
            "email" => "string|max:255|nullable",
            "inn" => "string|max:255|nullable",
            "kpp" => "string|max:255|nullable",
            "okpo" => "string|max:255|nullable",
            "ogrn" => "string|max:255|nullable",
            "okved" => "string|nullable",
            "ymetrika" => "string|max:255|nullable",
            "alert" => "string|max:500|nullable",
            "type" => "string|max:255|nullable",
            // "pos_surveys" => "integer|nullable",
            "district_id" => "integer|nullable",
            "privacy_policy" => "array|nullable",
            
            "poster" => "array|nullable",
            "poster.base64" => "string",
            "poster.name" => "required_with:poster.base64|filled|string",
            "poster.filename" => "required_with:poster.base64|filled|string",
            "poster.*.id" => "integer",
            "poster.*.delete" => "boolean",

            "social_networks" => "array|nullable",
            "social_networks.*.code" => "string|filled",
            "social_networks.*.link" => "string",
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
    
}
