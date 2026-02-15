<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EbookAPIController extends Controller
{
    /**
     * Display a listing of ebooks.
     */
    public function index(Request $request)
    {
        $query = Ebook::with(['user', 'category'])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->category) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->price_type) {
            if ($request->price_type === 'free') {
                $query->where('is_free', true);
            } elseif ($request->price_type === 'paid') {
                $query->where('is_free', false);
            }
        }

        $ebooks = $query->paginate(12);

        return response()->json([
            'data' => $ebooks->items(),
            'pagination' => [
                'current_page' => $ebooks->currentPage(),
                'last_page' => $ebooks->lastPage(),
                'per_page' => $ebooks->perPage(),
                'total' => $ebooks->total(),
            ]
        ]);
    }

    /**
     * Display the specified ebook.
     */
    public function show($id)
    {
        $ebook = Ebook::with(['user', 'category'])
            ->where('id', $id)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'id' => $ebook->id,
            'title' => $ebook->title,
            'slug' => $ebook->slug,
            'description' => $ebook->description,
            'excerpt' => $ebook->excerpt,
            'content' => $ebook->content,
            'price' => $ebook->price,
            'is_free' => $ebook->is_free,
            'is_published' => $ebook->is_published,
            'is_featured' => $ebook->is_featured,
            'file_size' => $ebook->file_size,
            'file_type' => $ebook->file_type,
            'cover_image' => $ebook->cover_image,
            'download_count' => $ebook->download_count,
            'view_count' => $ebook->view_count,
            'user' => $ebook->user,
            'category' => $ebook->category,
            'created_at' => $ebook->created_at,
            'updated_at' => $ebook->updated_at,
        ]);
    }

    /**
     * Store a newly created ebook.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|string',
            'file_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ebook = Ebook::create([
            'title' => $request->title,
            'slug' => \Illuminate\Support\Str::slug($request->title),
            'description' => $request->description,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'price' => $request->price,
            'is_free' => $request->is_free ?? false,
            'is_featured' => $request->is_featured ?? false,
            'is_published' => $request->is_published ?? false,
            'category_id' => $request->category_id,
            'user_id' => $request->user()->id,
            'cover_image' => $request->cover_image,
            'file_path' => $request->file_path,
        ]);

        return response()->json([
            'message' => 'Ebook created successfully',
            'ebook' => $ebook
        ], 201);
    }

    /**
     * Update the specified ebook.
     */
    public function update(Request $request, $id)
    {
        $ebook = Ebook::findOrFail($id);

        // Check if user owns this ebook
        if ($ebook->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'category_id' => 'sometimes|exists:categories,id',
            'cover_image' => 'nullable|string',
            'file_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ebook->update($request->all());

        return response()->json([
            'message' => 'Ebook updated successfully',
            'ebook' => $ebook
        ]);
    }

    /**
     * Remove the specified ebook.
     */
    public function destroy(Request $request, $id)
    {
        $ebook = Ebook::findOrFail($id);

        // Check if user owns this ebook
        if ($ebook->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $ebook->delete();

        return response()->json([
            'message' => 'Ebook deleted successfully'
        ]);
    }
}
