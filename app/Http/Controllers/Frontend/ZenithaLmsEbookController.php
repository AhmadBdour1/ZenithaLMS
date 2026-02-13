<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookAccess;
use App\Models\EbookFavorite;
use App\Models\EbookReview;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZenithaLmsEbookController extends Controller
{
    /**
     * Display the ebooks marketplace
     */
    public function index(Request $request)
    {
        $query = Ebook::with(['user', 'category'])
            ->where('status', 'active')
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Enhanced search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereJsonContains('ai_tags', $searchTerm);
            });
        }

        // ZenithaLMS: Advanced filtering
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('price_type')) {
            if ($request->price_type === 'free') {
                $query->where('is_free', true);
            } elseif ($request->price_type === 'paid') {
                $query->where('is_free', false);
            }
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        // ZenithaLMS: AI-powered recommendations
        $featuredEbooks = Ebook::with(['user', 'category'])
            ->where('status', 'active')
            ->where('is_featured', true)
            ->limit(6)
            ->get();

        $ebooks = $query->paginate(12);
        $categories = Category::where('is_active', true)->get();

        return view('zenithalms.ebooks.index', compact('ebooks', 'categories', 'featuredEbooks'));
    }

    /**
     * Display ebook details
     */
    public function show($slug)
    {
        $ebook = Ebook::with(['user', 'category', 'reviews.user'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // ZenithaLMS: AI-powered recommendations
        $similarEbooks = $ebook->getSimilarEbooks(4);
        $recommendedEbooks = Auth::check() ? $ebook->getRecommendedEbooks(Auth::id(), 4) : [];

        // ZenithaLMS: Check user access
        $hasAccess = Auth::check() ? $ebook->canBeAccessedBy(Auth::id()) : false;
        $isFavorited = Auth::check() ? $ebook->isFavoritedBy(Auth::id()) : false;

        // ZenithaLMS: Increment view count
        $ebook->increment('view_count');

        return view('zenithalms.ebooks.show', compact(
            'ebook', 
            'similarEbooks', 
            'recommendedEbooks', 
            'hasAccess', 
            'isFavorited'
        ));
    }

    /**
     * Display user's ebooks
     */
    public function myEbooks(Request $request)
    {
        $user = Auth::user();
        
        // ZenithaLMS: Get user's ebooks with access
        $query = Ebook::whereHas('accessRecords', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where(function ($q) {
                      $q->where('access_until', '>', now())
                        ->orWhereNull('access_until');
                  });
        })->with(['category', 'reviews']);

        // ZenithaLMS: Filter by tab
        $tab = $request->get('tab', 'purchased');
        
        if ($tab === 'purchased') {
            $ebooks = $query->where('is_free', false)->paginate(12);
        } elseif ($tab === 'free') {
            $ebooks = $query->where('is_free', true)->paginate(12);
        } elseif ($tab === 'favorites') {
            $ebooks = $user->favoriteEbooks()
                ->with(['category', 'reviews'])
                ->paginate(12);
        }

        return view('zenithalms.ebooks.my-ebooks', compact('ebooks', 'tab'));
    }

    /**
     * Add ebook to favorites
     */
    public function addToFavorites(Request $request, $ebookId)
    {
        $user = Auth::user();
        $ebook = Ebook::findOrFail($ebookId);

        // ZenithaLMS: Toggle favorite
        if ($ebook->isFavoritedBy($user->id)) {
            $user->favoriteEbooks()->detach($ebookId);
            $message = 'Ebook removed from favorites';
            $status = 'removed';
        } else {
            $user->favoriteEbooks()->attach($ebookId);
            $message = 'Ebook added to favorites';
            $status = 'added';
        }

        // ZenithaLMS: AI recommendation update
        $this->updateRecommendationProfile($user, $ebook);

        return response()->json([
            'message' => $message,
            'status' => $status,
            'is_favorited' => $ebook->isFavoritedBy($user->id),
        ]);
    }

    /**
     * Remove ebook from favorites
     */
    public function removeFromFavorites($ebookId)
    {
        $user = Auth::user();
        $ebook = Ebook::findOrFail($ebookId);

        $user->favoriteEbooks()->detach($ebookId);

        return response()->json([
            'message' => 'Ebook removed from favorites',
            'is_favorited' => false,
        ]);
    }

    /**
     * Download ebook
     */
    public function download($ebookId)
    {
        $user = Auth::user();
        $ebook = Ebook::findOrFail($ebookId);

        // ZenithaLMS: Check access
        if (!$ebook->canBeAccessedBy($user->id)) {
            abort(403, 'You do not have access to this ebook');
        }

        // ZenithaLMS: Check if downloadable
        if (!$ebook->is_downloadable) {
            abort(403, 'This ebook cannot be downloaded');
        }

        // ZenithaLMS: Increment download count
        $ebook->incrementDownloadCount();

        // ZenithaLMS: Log download
        $this->logDownload($user, $ebook);

        return Storage::download($ebook->file_path, $ebook->title . '.' . $ebook->file_type);
    }

    /**
     * Read ebook online
     */
    public function read($ebookId)
    {
        $user = Auth::user();
        $ebook = Ebook::findOrFail($ebookId);

        // ZenithaLMS: Check access
        if (!$ebook->canBeAccessedBy($user->id)) {
            abort(403, 'You do not have access to this ebook');
        }

        // ZenithaLMS: Track reading progress
        $this->trackReadingProgress($user, $ebook);

        return view('zenithalms.ebooks.read', compact('ebook'));
    }

    /**
     * Search ebooks
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json(['ebooks' => []]);
        }

        // ZenithaLMS: AI-powered search
        $ebooks = Ebook::with(['user', 'category'])
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhereJsonContains('ai_tags', $query);
            })
            ->limit(10)
            ->get();

        return response()->json(['ebooks' => $ebooks]);
    }

    /**
     * Get ebook recommendations
     */
    public function recommendations(Request $request)
    {
        $user = Auth::user();
        $ebookId = $request->get('ebook_id');
        
        if (!$user) {
            return response()->json(['recommendations' => []]);
        }

        $ebook = $ebookId ? Ebook::find($ebookId) : null;
        
        // ZenithaLMS: AI-powered recommendations
        $recommendations = $ebook 
            ? $ebook->getRecommendedEbooks($user->id, 6)
            : $user->getRecommendedCourses(6); // Reuse course recommendation logic

        return response()->json(['recommendations' => $recommendations]);
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function updateRecommendationProfile($user, $ebook)
    {
        // ZenithaLMS: Update user's recommendation profile based on favorite
        $currentProfile = $user->learning_profile ?? [];
        
        // Add ebook category to interests
        if ($ebook->category && !in_array($ebook->category->name, $currentProfile['interests'] ?? [])) {
            $currentProfile['interests'][] = $ebook->category->name;
            $user->updateLearningProfile($currentProfile);
        }
    }

    private function logDownload($user, $ebook)
    {
        // ZenithaLMS: Log download for analytics
        DB::table('ebook_downloads')->insert([
            'user_id' => $user->id,
            'ebook_id' => $ebook->id,
            'downloaded_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function trackReadingProgress($user, $ebook)
    {
        // ZenithaLMS: Track reading progress
        $progress = DB::table('ebook_reading_progress')
            ->where('user_id', $user->id)
            ->where('ebook_id', $ebook->id)
            ->first();

        if (!$progress) {
            DB::table('ebook_reading_progress')->insert([
                'user_id' => $user->id,
                'ebook_id' => $ebook->id,
                'started_at' => now(),
                'last_accessed_at' => now(),
                'reading_time_minutes' => 0,
                'pages_read' => 0,
            ]);
        } else {
            DB::table('ebook_reading_progress')
                ->where('id', $progress->id)
                ->update(['last_accessed_at' => now()]);
        }
    }

    /**
     * ZenithaLMS: Admin methods
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('zenithalms.ebooks.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_downloadable' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'required|file|mimes:pdf,epub,mobi|max:10240',
        ]);

        $ebookData = $request->except(['thumbnail', 'file']);
        $ebookData['slug'] = Str::slug($request->title);
        $ebookData['user_id'] = Auth::id();
        $ebookData['status'] = 'active';

        // ZenithaLMS: Handle file uploads
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('ebooks/thumbnails', 'public');
            $ebookData['thumbnail'] = $thumbnailPath;
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('ebooks/files', 'public');
            $ebookData['file_path'] = $filePath;
            $ebookData['file_type'] = $file->getClientOriginalExtension();
            $ebookData['file_size'] = $file->getSize();
        }

        $ebook = Ebook::create($ebookData);

        // ZenithaLMS: Generate AI tags
        $ebook->generateAiTags();

        return redirect()->route('zenithalms.ebooks.show', $ebook->slug)
            ->with('success', 'Ebook created successfully!');
    }

    public function edit($id)
    {
        $ebook = Ebook::where('user_id', Auth::id())->findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        
        return view('zenithalms.ebooks.edit', compact('ebook', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $ebook = Ebook::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_downloadable' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|file|mimes:pdf,epub,mobi|max:10240',
        ]);

        $ebookData = $request->except(['thumbnail', 'file']);
        $ebookData['slug'] = Str::slug($request->title);

        // ZenithaLMS: Handle file uploads
        if ($request->hasFile('thumbnail')) {
            if ($ebook->thumbnail) {
                Storage::disk('public')->delete($ebook->thumbnail);
            }
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('ebooks/thumbnails', 'public');
            $ebookData['thumbnail'] = $thumbnailPath;
        }

        if ($request->hasFile('file')) {
            if ($ebook->file_path) {
                Storage::disk('public')->delete($ebook->file_path);
            }
            $file = $request->file('file');
            $filePath = $file->store('ebooks/files', 'public');
            $ebookData['file_path'] = $filePath;
            $ebookData['file_type'] = $file->getClientOriginalExtension();
            $ebookData['file_size'] = $file->getSize();
        }

        $ebook->update($ebookData);

        // ZenithaLMS: Regenerate AI tags
        $ebook->generateAiTags();

        return redirect()->route('zenithalms.ebooks.show', $ebook->slug)
            ->with('success', 'Ebook updated successfully!');
    }

    public function destroy($id)
    {
        $ebook = Ebook::where('user_id', Auth::id())->findOrFail($id);

        // ZenithaLMS: Delete files
        if ($ebook->thumbnail) {
            Storage::disk('public')->delete($ebook->thumbnail);
        }
        if ($ebook->file_path) {
            Storage::disk('public')->delete($ebook->file_path);
        }

        $ebook->delete();

        return redirect()->route('zenithalms.ebooks.index')
            ->with('success', 'Ebook deleted successfully!');
    }
}
