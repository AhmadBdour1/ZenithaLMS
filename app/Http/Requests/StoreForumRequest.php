<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be enhanced with role-based authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|min:3',
            'content' => 'required|string|min:10|max:5000',
            'category' => 'required|string|max:50|in:general,technical,support,announcement',
            'course_id' => 'nullable|exists:courses,id',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Forum title is required',
            'title.min' => 'Forum title must be at least 3 characters',
            'content.required' => 'Forum content is required',
            'content.min' => 'Forum content must be at least 10 characters',
            'category.required' => 'Forum category is required',
            'category.in' => 'Invalid forum category',
        ];
    }
}
