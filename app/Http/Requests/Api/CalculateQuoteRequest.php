<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CalculateQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'items.*.check_in' => ['required', 'date', 'after_or_equal:today'],
            'items.*.check_out' => ['required', 'date', 'after:items.*.check_in'],
            'services' => ['nullable', 'array'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'services.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
