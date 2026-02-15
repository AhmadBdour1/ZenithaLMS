<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class EbookDownloadController extends Controller
{
    use AuthorizesRequests;
    
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Download ebook file
     */
    public function download(Request $request, $id)
    {
        // Return 401 if unauthenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $ebook = Ebook::findOrFail($id);

        // Enforce authorization using policy
        $this->authorize('download', $ebook);

        if (empty($ebook->file_path)) {
            return response()->json([
                'message' => 'Ebook file not available'
            ], 404);
        }

        // Normalize path and check if file exists on private disk
        $normalizedPath = $this->mediaService->normalizePath($ebook->file_path);
        if (!Storage::disk('private')->exists($normalizedPath)) {
            return response()->json([
                'message' => 'Ebook file not found'
            ], 404);
        }

        // Increment download count
        $ebook->incrementDownloadCount();

        // Return file download from private storage
        return Storage::disk('private')->download($normalizedPath, $ebook->slug . '.' . $ebook->file_type);
    }

}
