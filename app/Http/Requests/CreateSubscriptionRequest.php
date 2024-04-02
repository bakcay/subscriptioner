<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credit_card'  => 'required|string|min:16|max:20',
            'expire_month' => 'required|string|min:2|max:2',
            'expire_year'  => 'required|string|min:4|max:4',
            'cvv'          => 'required|string|min:3|max:4',
        ];
    }
}
