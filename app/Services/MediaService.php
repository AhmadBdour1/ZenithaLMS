<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Store uploaded file under public disk and return relative path
     */
    public function storePublic(
        UploadedFile $file, 
        string $folder, 
        ?string $filenameBase = null, 
        array $allowedExtensions = [], 
        int $maxBytes = 5242880
    ): string {
        // Validate file size
        if ($file->getSize() > $maxBytes) {
            throw new \InvalidArgumentException("File size exceeds maximum allowed size of {$maxBytes} bytes");
        }

        // Validate extension if specified
        if (!empty($allowedExtensions)) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, array_map('strtolower', $allowedExtensions))) {
                throw new \InvalidArgumentException("File extension '{$extension}' is not allowed");
            }
        }

        // Generate safe filename
        $filenameBase = $filenameBase ?: Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $filename = $filenameBase . '-' . Str::random(8) . '.' . $extension;

        // Store file
        $path = $file->storeAs($folder, $filename, 'public');
        
        return $path;
    }

    /**
     * Delete file from public disk
     */
    public function deletePublic(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Normalize path - remove leading "storage/" if exists
        $normalizedPath = ltrim($path, 'storage/');
        
        // Delete from disk if exists
        if (Storage::disk('public')->exists($normalizedPath)) {
            Storage::disk('public')->delete($normalizedPath);
        }
    }

    /**
     * Get public URL for media file with fallback
     */
    public function url(?string $path, string $fallback = '/images/course-placeholder.png'): string
    {
        if (empty($path)) {
            return $fallback;
        }

        // Normalize path - remove leading "storage/" if exists
        $normalizedPath = ltrim($path, 'storage/');
        
        // Check if file exists and return URL, otherwise return fallback
        if (Storage::disk('public')->exists($normalizedPath)) {
            return Storage::disk('public')->url($normalizedPath);
        }
        
        return $fallback;
    }
}
