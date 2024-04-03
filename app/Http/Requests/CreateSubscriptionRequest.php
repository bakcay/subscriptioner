<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'credit_card'  => 'required|numeric|digits_between:16,20',
            'expire_month' => 'required|numeric|digits:2',
            'expire_year'  => 'required|numeric|digits:2',
            'cvv'          => 'required|numeric|digits_between:3,4',
            'card_owner'   => 'nullable|string|min:3|max:255',
        ];
    }
}
