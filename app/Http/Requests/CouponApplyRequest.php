<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponApplyRequest extends FormRequest
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
            'coupon_code' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'coupon_code.required' => 'Coupon code is required',
            'coupon_code.max' => 'Coupon code is too long',
            'total_amount.required' => 'Total amount is required',
            'total_amount.numeric' => 'Total amount must be a number',
            'total_amount.min' => 'Total amount cannot be negative',
        ];
    }
}
