<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVirtualClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be enhanced with instructor role check
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
            'description' => 'required|string|min:10|max:2000',
            'course_id' => 'nullable|exists:courses,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'meeting_link' => 'required|url|max:500',
            'meeting_id' => 'required|string|max:100|unique:virtual_classes,meeting_id',
            'password' => 'nullable|string|max:50',
            'max_participants' => 'required|integer|min:1|max:1000',
            'is_recorded' => 'boolean',
            'recording_url' => 'nullable|url|max:500',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Virtual class title is required',
            'description.required' => 'Virtual class description is required',
            'start_time.after' => 'Start time must be in the future',
            'end_time.after' => 'End time must be after start time',
            'meeting_link.required' => 'Meeting link is required',
            'meeting_link.url' => 'Meeting link must be a valid URL',
            'meeting_id.unique' => 'Meeting ID must be unique',
        ];
    }
}
