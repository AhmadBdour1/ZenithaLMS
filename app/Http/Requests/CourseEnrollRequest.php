<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseEnrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $courseId = $this->route('courseId');
        $course = \App\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        // All authenticated users can enroll in any course for now
        // This could be restricted based on course visibility or user roles
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_method' => 'nullable|string|in:wallet,stripe,paypal',
            'payment_details' => 'nullable|array',
            'coupon_code' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'Invalid payment method selected',
            'coupon_code.max' => 'Coupon code is too long',
        ];
    }
}
