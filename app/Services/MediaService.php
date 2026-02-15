<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /**
     * Store uploaded file under public disk and return relative path
     */
    public function storePublic(
        UploadedFile $file, 
        string $folderKey, 
        ?string $oldPath = null
    ): string {
        // Validate upload before storing
        $this->validateUpload($file, $folderKey);
        
        $folder = $this->getFolder($folderKey);
        
        // Delete old file if specified
        if ($oldPath) {
            $this->deletePublic($oldPath);
        }
        
        // Store file with hash name
        $filename = $file->hashName();
        $path = $file->storeAs($folder, $filename, 'public');
        
        return $path;
    }

    /**
     * Store uploaded file under private disk and return relative path
     */
    public function storePrivate(
        UploadedFile $file, 
        string $folderKey, 
        ?string $oldPath = null
    ): string {
        // Validate upload before storing
        $this->validateUpload($file, $folderKey);
        
        $folder = $this->getFolder($folderKey);
        
        // Delete old file if specified
        if ($oldPath) {
            $this->deletePrivate($oldPath);
        }
        
        // Store file with hash name on private disk
        $filename = $file->hashName();
        $path = $file->storeAs($folder, $filename, 'private');
        
        return $path;
    }

    /**
     * Validate upload before storing
     */
    private function validateUpload(UploadedFile $file, string $folderKey): void
    {
        $rules = config("media.validation.{$folderKey}");
        
        if (!$rules) {
            throw ValidationException::withMessages([
                'folder' => "Invalid folder key: {$folderKey}"
            ]);
        }

        // Validate file size
        $maxSizeBytes = $rules['max_size_kb'] * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            throw ValidationException::withMessages([
                'file' => "File size exceeds maximum allowed size of {$rules['max_size_kb']}KB"
            ]);
        }

        // Validate extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $rules['allowed_extensions'])) {
            throw ValidationException::withMessages([
                'file' => "File extension '{$extension}' is not allowed. Allowed extensions: " . implode(', ', $rules['allowed_extensions'])
            ]);
        }

        // Validate MIME type with JPEG robustness
        $mimeType = $file->getMimeType();
        
        // For JPEG files, accept both 'image/jpeg' and 'image/jpg' but prefer 'image/jpeg'
        if ($extension === 'jpg' || $extension === 'jpeg') {
            if (!in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
                throw ValidationException::withMessages([
                    'file' => "JPEG file must have MIME type 'image/jpeg' or 'image/jpg', got '{$mimeType}'"
                ]);
            }
        } else {
            // For all other files, require exact MIME match
            if (!in_array($mimeType, $rules['allowed_mimes'])) {
                throw ValidationException::withMessages([
                    'file' => "File MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $rules['allowed_mimes'])
                ]);
            }
        }

        // Defend against dangerous extensions in filename (case-insensitive)
        $originalName = strtolower($file->getClientOriginalName());
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phpt', 'pl', 'py', 'cgi', 'sh', 'bat', 'exe', 'com', 'js', 'vbs', 'asp', 'aspx', 'jsp'];
        
        foreach ($dangerousExtensions as $dangerousExt) {
            // Check if dangerous extension appears anywhere in filename
            if (strpos($originalName, ".{$dangerousExt}.") !== false || 
                str_ends_with($originalName, ".{$dangerousExt}") ||
                strpos($originalName, ".{$dangerousExt}") !== false) {
                throw ValidationException::withMessages([
                    'file' => "File name contains potentially dangerous extension: {$dangerousExt}"
                ]);
            }
        }
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
     * Delete file from private disk
     */
    public function deletePrivate(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Normalize path - remove leading "storage/" if exists
        $normalizedPath = $this->normalizePath($path);
        
        // Delete from disk if exists
        if (Storage::disk('private')->exists($normalizedPath)) {
            Storage::disk('private')->delete($normalizedPath);
        }
    }

    /**
     * Get public URL for media file with fallback
     */
    public function publicUrl(?string $path, string $fallback = '/images/course-placeholder.png'): string
    {
        if (empty($path)) {
            return $fallback;
        }

        // Normalize path - remove leading "storage/" if exists
        $normalizedPath = $this->normalizePath($path);
        
        // Check if file exists and return URL, otherwise return fallback
        if (Storage::disk('public')->exists($normalizedPath)) {
            return Storage::disk('public')->url($normalizedPath);
        }
        
        return $fallback;
    }

    /**
     * Normalize path by stripping leading 'storage/' and extracting disk-relative from full URLs
     */
    public function normalizePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Remove leading 'storage/' if exists (exact match, not individual characters)
        if (str_starts_with($path, 'storage/')) {
            $normalizedPath = substr($path, 8); // Remove 'storage/' (8 characters)
        } else {
            $normalizedPath = $path;
        }
        
        // If it's a full URL, extract the path part
        if (str_starts_with($normalizedPath, 'http')) {
            $parsed = parse_url($normalizedPath);
            $normalizedPath = $parsed['path'] ?? '';
            // Remove leading '/storage' from URL paths
            if (str_starts_with($normalizedPath, '/storage')) {
                $normalizedPath = substr($normalizedPath, 8); // Remove '/storage' (8 characters)
            }
            // Remove leading slash if exists
            $normalizedPath = ltrim($normalizedPath, '/');
        }
        
        return $normalizedPath;
    }

    /**
     * Get folder path from config
     */
    public function getFolder(string $key): string
    {
        return config("media.folders.{$key}", $key);
    }

    /**
     * Get media limits from config
     */
    public function getLimits(string $type): array
    {
        return config("media.limits.{$type}", [
            'max_size' => 5 * 1024 * 1024, // 5MB default
            'allowed' => ['jpeg', 'jpg', 'png', 'webp'],
        ]);
    }

    /**
     * Get fallback URL from config
     */
    public function getFallback(string $key): string
    {
        return config("media.fallbacks.{$key}", '/images/course-placeholder.png');
    }
}
