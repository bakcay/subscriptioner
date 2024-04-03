<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'name'=> ['nullable', 'string', 'max:255'],
            'address'=> ['nullable', 'string', 'max:255'],
            'city'=> ['nullable', 'string', 'max:25'],
            'country'=> ['nullable', 'string', 'max:2'],
            'phone'=> ['nullable', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'tax_office'=> ['nullable', 'string', 'max:64'],
            'tax_number'=> ['nullable', 'string', 'max:20'],
        ];
    }


    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'email.max' => 'Email is too long',
            'email.unique' => 'Email is already taken',
            'password.required' => 'Password is required',
            'password.min' => 'Password is too short',
            'name.string' => 'Name must be a string',
            'name.max' => 'Name is too long',
            'address1.string' => 'Address1 must be a string',
            'address1.max' => 'Address1 is too long',
            'city.string' => 'City must be a string',
            'city.max' => 'City is too long',
            'region.string' => 'Region must be a string',
            'region.max' => 'Region is too long',
            'country.string' => 'Country must be a string',
            'country.max' => 'Country is too long',
            'phone.string' => 'Phone must be a string',
            'phone.max' => 'Phone is too long',
            'tax_office.string' => 'Tax office must be a string',
            'tax_office.max' => 'Tax office is too long',
            'tax_number.string' => 'Tax number must be a string',
            'tax_number.max' => 'Tax number is too long',

        ];
    }


}
