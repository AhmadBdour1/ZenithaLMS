<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletAddFundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1|max:10000',
            'payment_method' => 'required|string|in:stripe,paypal,bank_transfer',
            'payment_details' => 'required|array',
            'payment_details.card_number' => 'required_if:payment_method,stripe|string|max:20',
            'payment_details.exp_month' => 'required_if:payment_method,stripe|integer|min:1|max:12',
            'payment_details.exp_year' => 'required_if:payment_method,stripe|integer|min:' . date('Y') . '|max:' . (date('Y') + 10),
            'payment_details.cvc' => 'required_if:payment_method,stripe|string|max:4',
            'payment_details.paypal_email' => 'required_if:payment_method,paypal|email|max:255',
            'payment_details.bank_account' => 'required_if:payment_method,bank_transfer|string|max:50',
            'payment_details.routing_number' => 'required_if:payment_method,bank_transfer|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Minimum amount is $1',
            'amount.max' => 'Maximum amount is $10,000',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
            'payment_details.required' => 'Payment details are required',
            'payment_details.card_number.required_if' => 'Card number is required for Stripe payments',
            'payment_details.exp_month.required_if' => 'Expiration month is required for Stripe payments',
            'payment_details.exp_year.required_if' => 'Expiration year is required for Stripe payments',
            'payment_details.cvc.required_if' => 'CVC is required for Stripe payments',
            'payment_details.paypal_email.required_if' => 'PayPal email is required for PayPal payments',
            'payment_details.paypal_email.email' => 'Please enter a valid PayPal email address',
            'payment_details.bank_account.required_if' => 'Bank account number is required for bank transfers',
            'payment_details.routing_number.required_if' => 'Routing number is required for bank transfers',
        ];
    }
}
