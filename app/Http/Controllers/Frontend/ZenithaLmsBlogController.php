<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ZenithaLmsBlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display blog listing
     */
    public function index(Request $request)
    {
        $query = Blog::with(['user', 'category', 'tags'])
            ->published()
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Enhanced search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('excerpt', 'like', '%' . $searchTerm . '%')
                  ->orWhere('content', 'like', '%' . $searchTerm . '%');
            });
        }

        // ZenithaLMS: Category filtering
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // ZenithaLMS: Tag filtering
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        // ZenithaLMS: Featured posts
        $featuredPosts = Blog::with(['user', 'category'])
            ->published()
            ->featured()
            ->limit(3)
            ->get();

        $blogs = $query->paginate(12);
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::orderBy('name')->get();

        return view('zenithalms.blog.index', compact('blogs', 'categories', 'tags', 'featuredPosts'));
    }

    /**
     * Display blog post details
     */
    public function show($slug)
    {
        $blog = Blog::with(['user', 'category', 'tags', 'comments.user'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // ZenithaLMS: Increment view count
        $blog->incrementViewCount();

        // ZenithaLMS: AI-powered related posts
        $relatedPosts = $blog->getRelatedPosts(4);

        // ZenithaLMS: Generate AI summary if not exists
        if (!$blog->ai_summary) {
            $blog->generateAiSummary();
        }

        return view('zenithalms.blog.show', compact('blog', 'relatedPosts'));
    }

    /**
     * Display user's blog posts
     */
    public function myBlogs(Request $request)
    {
        $user = Auth::user();
        
        $query = Blog::where('user_id', $user->id)
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $blogs = $query->paginate(12);

        return view('zenithalms.blog.my-blogs', compact('blogs'));
    }

    /**
     * Show form to create blog post
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::orderBy('name')->get();
        
        return view('zenithalms.blog.create', compact('categories', 'tags'));
    }

    /**
     * Store new blog post
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'status' => 'required|in:draft,published',
        ]);

        $blogData = $request->except(['featured_image', 'tags']);
        $blogData['slug'] = Str::slug($request->title);
        $blogData['user_id'] = Auth::id();

        // ZenithaLMS: Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $image = $request->file('featured_image');
            $imagePath = $image->store('blog/images', 'public');
            $blogData['featured_image'] = $imagePath;
        }

        $blog = Blog::create($blogData);

        // ZenithaLMS: Attach tags
        if ($request->has('tags')) {
            $blog->tags()->attach($request->tags);
        }

        // ZenithaLMS: Generate AI summary
        if ($blog->status === 'published') {
            $blog->generateAiSummary();
        }

        return redirect()->route('zenithalms.blog.show', $blog->slug)
            ->with('success', 'Blog post created successfully!');
    }

    /**
     * Show form to edit blog post
     */
    public function edit($id)
    {
        $blog = Blog::where('user_id', Auth::id())->findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::orderBy('name')->get();
        
        return view('zenithalms.blog.edit', compact('blog', 'categories', 'tags'));
    }

    /**
     * Update blog post
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'status' => 'required|in:draft,published',
        ]);

        $blogData = $request->except(['featured_image', 'tags']);
        $blogData['slug'] = Str::slug($request->title);

        // ZenithaLMS: Handle featured image upload
        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }
            $image = $request->file('featured_image');
            $imagePath = $image->store('blog/images', 'public');
            $blogData['featured_image'] = $imagePath;
        }

        $blog->update($blogData);

        // ZenithaLMS: Sync tags
        if ($request->has('tags')) {
            $blog->tags()->sync($request->tags);
        } else {
            $blog->tags()->detach();
        }

        // ZenithaLMS: Regenerate AI summary if published
        if ($blog->status === 'published') {
            $blog->generateAiSummary();
        }

        return redirect()->route('zenithalms.blog.show', $blog->slug)
            ->with('success', 'Blog post updated successfully!');
    }

    /**
     * Delete blog post
     */
    public function destroy($id)
    {
        $blog = Blog::where('user_id', Auth::id())->findOrFail($id);

        // ZenithaLMS: Delete featured image
        if ($blog->featured_image) {
            Storage::disk('public')->delete($blog->featured_image);
        }

        $blog->delete();

        return redirect()->route('zenithalms.blog.my-blogs')
            ->with('success', 'Blog post deleted successfully!');
    }

    /**
     * Search blog posts
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json(['posts' => []]);
        }

        // ZenithaLMS: AI-powered search
        $posts = Blog::with(['user', 'category'])
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('excerpt', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();

        return response()->json(['posts' => $posts]);
    }

    /**
     * Get blog recommendations
     */
    public function recommendations(Request $request)
    {
        $user = Auth::user();
        $blogId = $request->get('blog_id');
        
        if (!$user) {
            return response()->json(['recommendations' => []]);
        }

        $blog = $blogId ? Blog::find($blogId) : null;
        
        // ZenithaLMS: AI-powered recommendations
        $recommendations = $blog 
            ? $blog->getRelatedPosts(6)
            : $this->getUserRecommendations($user, 6);

        return response()->json(['recommendations' => $recommendations]);
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function getUserRecommendations($user, $limit)
    {
        // ZenithaLMS: Get recommendations based on user's reading history
        $readPosts = $user->blogViews()
            ->with('blog.category')
            ->get()
            ->pluck('blog.category_id')
            ->unique();

        return Blog::with(['user', 'category'])
            ->published()
            ->whereIn('category_id', $readPosts)
            ->where('user_id', '!=', $user->id)
            ->limit($limit)
            ->get();
    }

    /**
     * ZenithaLMS: Admin methods
     */
    public function adminIndex(Request $request)
    {
        $this->authorize('manage_blogs');
        
        $query = Blog::with(['user', 'category'])
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ZenithaLMS: Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $blogs = $query->paginate(20);
        $users = User::orderBy('name')->get();
        $categories = Category::where('is_active', true)->get();

        return view('zenithalms.blog.admin.index', compact('blogs', 'users', 'categories'));
    }

    public function adminShow($id)
    {
        $this->authorize('manage_blogs');
        
        $blog = Blog::with(['user', 'category', 'tags', 'comments.user'])
            ->findOrFail($id);

        return view('zenithalms.blog.admin.show', compact('blog'));
    }

    public function adminEdit($id)
    {
        $this->authorize('manage_blogs');
        
        $blog = Blog::findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::orderBy('name')->get();
        
        return view('zenithalms.blog.admin.edit', compact('blog', 'categories', 'tags'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $this->authorize('manage_blogs');
        
        $blog = Blog::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'status' => 'required|in:draft,published',
        ]);

        $blogData = $request->except(['featured_image', 'tags']);
        $blogData['slug'] = Str::slug($request->title);

        // ZenithaLMS: Handle featured image upload
        if ($request->hasFile('featured_image')) {
            if ($blog->featured_image) {
                Storage::disk('public')->delete($blog->featured_image);
            }
            $image = $request->file('featured_image');
            $imagePath = $image->store('blog/images', 'public');
            $blogData['featured_image'] = $imagePath;
        }

        $blog->update($blogData);

        // ZenithaLMS: Sync tags
        if ($request->has('tags')) {
            $blog->tags()->sync($request->tags);
        } else {
            $blog->tags()->detach();
        }

        // ZenithaLMS: Regenerate AI summary if published
        if ($blog->status === 'published') {
            $blog->generateAiSummary();
        }

        return redirect()->route('zenithalms.blog.admin.show', $blog->id)
            ->with('success', 'Blog post updated successfully!');
    }

    public function adminDestroy($id)
    {
        $this->authorize('manage_blogs');
        
        $blog = Blog::findOrFail($id);

        // ZenithaLMS: Delete featured image
        if ($blog->featured_image) {
            Storage::disk('public')->delete($blog->featured_image);
        }

        $blog->delete();

        return redirect()->route('zenithalms.blog.admin.index')
            ->with('success', 'Blog post deleted successfully!');
    }
}
