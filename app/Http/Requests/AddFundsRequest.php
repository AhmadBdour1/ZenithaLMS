<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddFundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1|max:10000',
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer',
            'payment_details' => 'required|array',
            'payment_details.payment_method_id' => 'required|string|max:100',
            'payment_details.last_four' => 'nullable|string|digits:4',
            'payment_details.card_brand' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be at least 1',
            'amount.max' => 'Amount cannot exceed 10000',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'payment_details.required' => 'Payment details are required',
            'payment_details.payment_method_id.required' => 'Payment method ID is required',
        ];
    }
}
