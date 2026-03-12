<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
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
            'description' => 'required|string|max:1000|min:10',
            'course_id' => 'nullable|exists:courses,id',
            'duration_minutes' => 'required|integer|min:5|max:300',
            'attempts_allowed' => 'required|integer|min:1|max:10',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string|min:5|max:500',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.points' => 'required|integer|min:1|max:100',
            'questions.*.options' => 'required_if:questions.*.type,multiple_choice|array|min:2',
            'questions.*.options.*.option' => 'required|string|max:255',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Quiz title is required',
            'title.min' => 'Quiz title must be at least 3 characters',
            'description.required' => 'Quiz description is required',
            'duration_minutes.min' => 'Quiz duration must be at least 5 minutes',
            'questions.required' => 'At least one question is required',
            'questions.*.question.required' => 'Question text is required',
            'questions.*.options.required_if' => 'Multiple choice questions must have at least 2 options',
        ];
    }
}
