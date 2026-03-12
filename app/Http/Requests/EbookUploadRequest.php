<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EbookUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->canCreateContent();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'required|file|mimes:pdf,epub,mobi|max:10240',
            'language' => 'nullable|string|max:10',
            'pages' => 'nullable|integer|min:1',
            'file_size' => 'nullable|integer|min:1',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Ebook title is required',
            'description.required' => 'Ebook description is required',
            'category_id.required' => 'Please select a category',
            'category_id.exists' => 'Selected category does not exist',
            'price.required' => 'Ebook price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price cannot be negative',
            'thumbnail.image' => 'Thumbnail must be an image file',
            'thumbnail.mimes' => 'Thumbnail must be a JPEG, PNG, JPG, or GIF file',
            'thumbnail.max' => 'Thumbnail size must not exceed 2MB',
            'file.required' => 'Ebook file is required',
            'file.file' => 'Please upload a valid file',
            'file.mimes' => 'Ebook must be a PDF, EPUB, or MOBI file',
            'file.max' => 'Ebook file size must not exceed 10MB',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
        ];
    }
}
