<?php
namespace App\Modules\Workers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use Carbon\Carbon;
use App\Helpers\Api\ApiResponse;

class WorkerRequest extends FormRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array {
        return [
            "surname" => "required|string|max:255",
            "name" => "required|string|max:255",
            "second_name" => "string|max:255|nullable",
            "position" => "required|string|max:355|nullable",

            "email" => "string|max:255|nullable",
            "phone" => "string|max:255|nullable",

            // Массивы
            "biography" => "array|nullable",
            "credentials" => "array|nullable",

            'documents' => 'array|nullable',
            'documents.*' => 'integer',

            'social_networks' => 'array|nullable',
            'social_networks.*.id' => 'integer|nullable',
            "social_networks.*.code" => "string|filled",
            "social_networks.*.link" => "string",
        ];
    }

    public function failedValidation(Validator $validator) {
        return ApiResponse::validateException(400, "Validation errors", $errors = $validator->errors());
    }
}
