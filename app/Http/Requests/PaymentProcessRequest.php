<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentProcessRequest extends FormRequest
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
            'payment_gateway' => 'required|exists:payment_gateways,gateway_code',
            'coupon_code' => 'nullable|string|max:50',
            'use_wallet' => 'boolean',
            'payment_method' => 'required_if:use_wallet,false|string|in:stripe,paypal,bank_transfer',
            'payment_details' => 'required_if:use_wallet,false|array',
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
            'payment_gateway.required' => 'Payment gateway is required',
            'payment_gateway.exists' => 'Selected payment gateway is not available',
            'coupon_code.max' => 'Coupon code is too long',
            'payment_method.required_if' => 'Payment method is required when not using wallet',
            'payment_method.in' => 'Invalid payment method selected',
            'payment_details.required_if' => 'Payment details are required when not using wallet',
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
