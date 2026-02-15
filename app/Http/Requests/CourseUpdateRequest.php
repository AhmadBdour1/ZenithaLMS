<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');
        
        if (!$course) {
            return false;
        }

        // Admin can update any course
        if ($this->user()->isAdmin()) {
            return true;
        }

        // Instructor can update their own course
        if ($this->user()->isInstructor() && $course->instructor_id === $this->user()->id) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'price' => 'sometimes|required|numeric|min:0',
            'is_free' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preview_video' => 'nullable|string|max:500',
            'language' => 'nullable|string|max:10',
            'duration_hours' => 'nullable|integer|min:1',
            'requirements' => 'nullable|array',
            'what_you_will_learn' => 'nullable|array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required',
            'description.required' => 'Course description is required',
            'category_id.required' => 'Please select a category',
            'category_id.exists' => 'Selected category does not exist',
            'level.required' => 'Please select a difficulty level',
            'level.in' => 'Invalid difficulty level selected',
            'price.required' => 'Course price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price cannot be negative',
            'thumbnail.image' => 'Thumbnail must be an image file',
            'thumbnail.mimes' => 'Thumbnail must be a JPEG, PNG, JPG, or GIF file',
            'thumbnail.max' => 'Thumbnail size must not exceed 2MB',
        ];
    }
}
