<?php
namespace App\Modules\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

// Helpers
use App\Helpers\Api\ApiResponse;

class ContractRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array {
        return [
            // Обязательные поля
            'number' => 'required|string|max:255',
            'price' => 'required|string|max:255',
            'site_id' => 'required|integer',
            
            // Текстовые
            'comment' => 'nullable|string|max:255',

            // Даты
            'published_at' => 'date',
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Номер контракта не может быть пустым',
            'number.string' => 'Номер контракта должно быть строкой',
            'number.max' => 'Допустимая длина Номера контракта не более 255 символов',

            'price.required' => 'Поле стоимость не может быть пустым',
            'price.string' => 'Стоимость должно быть строкой',
            'price.max' => 'Допустимая длина поля стоимость не более 255 символов',

            'site_id.required' => 'Сайт не может быть пустым',
            'site_id.integer' => 'Поле сайт должно быть числовым',
            
            'published_at.date' => 'Дата публикации должна быть в формате даты',
            'date_start.date' => 'Дата начала должна быть в формате даты',
            'date_end.date' => 'Дата окончания должна быть в формате даты',
        ];
    }

    public function failedValidation(Validator $validator) {
        return ApiResponse::validateException(400, 'Validation errors', $errors = $validator->errors());
    }
}
