<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuraPage;
use App\Models\AuraTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuraPageBuilderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all pages
     */
    public function index(Request $request)
    {
        $query = AuraPage::with(['author', 'template', 'parent']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by author
        if ($request->author) {
            $query->byAuthor($request->author);
        }

        // Filter by template
        if ($request->template) {
            $query->byTemplate($request->template);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%")
                  ->orWhere('content', 'LIKE', "%{$request->search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $pages = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'description' => $page->description,
                    'status' => $page->status,
                    'visibility' => $page->visibility,
                    'template' => $page->template ? [
                        'id' => $page->template->id,
                        'name' => $page->template->name,
                        'slug' => $page->template->slug,
                    ] : null,
                    'author' => [
                        'id' => $page->author->id,
                        'name' => $page->author->name,
                        'avatar' => $page->author->avatar,
                    ],
                    'featured_image' => $page->featured_image,
                    'is_homepage' => $page->is_homepage,
                    'show_in_menu' => $page->show_in_menu,
                    'view_count' => $page->view_count,
                    'seo_score' => $page->seo_score,
                    'published_at' => $page->published_at?->format('Y-m-d H:i'),
                    'created_at' => $page->created_at->format('Y-m-d H:i'),
                    'updated_at' => $page->updated_at->format('Y-m-d H:i'),
                ];
            }),
            'pagination' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    /**
     * Get single page
     */
    public function show($id)
    {
        $page = AuraPage::with(['author', 'template', 'parent', 'children', 'revisions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'description' => $page->description,
                'content' => $page->content,
                'blocks_data' => $page->blocks_data,
                'template_data' => $page->template_data,
                'custom_css' => $page->custom_css,
                'custom_js' => $page->custom_js,
                'status' => $page->status,
                'visibility' => $page->visibility,
                'password' => $page->password,
                'template' => $page->template,
                'author' => $page->author,
                'parent' => $page->parent,
                'children' => $page->children,
                'featured_image' => $page->featured_image,
                'gallery_images' => $page->gallery_images,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'og_image' => $page->og_image,
                'canonical_url' => $page->canonical_url,
                'is_homepage' => $page->is_homepage,
                'is_blog_page' => $page->is_blog_page,
                'is_contact_page' => $page->is_contact_page,
                'is_landing_page' => $page->is_landing_page,
                'show_in_menu' => $page->show_in_menu,
                'show_in_search' => $page->show_in_search,
                'allow_comments' => $page->allow_comments,
                'require_login' => $page->require_login,
                'redirect_url' => $page->redirect_url,
                'redirect_type' => $page->redirect_type,
                'cache_duration' => $page->cache_duration,
                'is_cached' => $page->is_cached,
                'view_count' => $page->view_count,
                'seo_score' => $page->seo_score,
                'read_time' => $page->read_time,
                'word_count' => $page->word_count,
                'published_at' => $page->published_at?->format('Y-m-d H:i'),
                'scheduled_at' => $page->scheduled_at?->format('Y-m-d H:i'),
                'expires_at' => $page->expires_at?->format('Y-m-d H:i'),
                'created_at' => $page->created_at->format('Y-m-d H:i'),
                'updated_at' => $page->updated_at->format('Y-m-d H:i'),
                'revisions' => $page->revisions->map(function ($revision) {
                    return [
                        'id' => $revision->id,
                        'title' => $revision->title,
                        'created_at' => $revision->created_at->format('Y-m-d H:i'),
                        'author' => $revision->author->name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Create new page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'template_id' => 'nullable|exists:aura_templates,id',
            'status' => 'required|in:draft,published,scheduled,archived',
            'visibility' => 'required|in:public,private,password_protected',
            'password' => 'required_if:visibility,password_protected|string|min:6',
            'featured_image' => 'nullable|url',
            'gallery_images' => 'array',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'blocks_data' => 'array',
            'template_data' => 'array',
            'is_homepage' => 'boolean',
            'is_blog_page' => 'boolean',
            'is_contact_page' => 'boolean',
            'is_landing_page' => 'boolean',
            'parent_id' => 'nullable|exists:aura_pages,id',
            'show_in_menu' => 'boolean',
            'show_in_search' => 'boolean',
            'allow_comments' => 'boolean',
            'require_login' => 'boolean',
            'redirect_url' => 'nullable|url',
            'redirect_type' => 'required_if:redirect_url|string|in:none,internal,external',
            'cache_duration' => 'nullable|integer|min:60',
            'is_cached' => 'boolean',
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:published_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check if homepage already exists
        if ($request->is_homepage) {
            AuraPage::where('is_homepage', true)->update(['is_homepage' => false]);
        }

        $page = AuraPage::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'content' => $request->content,
            'template_id' => $request->template_id,
            'status' => $request->status,
            'visibility' => $request->visibility,
            'password' => $request->password,
            'author_id' => $user->id,
            'featured_image' => $request->featured_image,
            'gallery_images' => $request->gallery_images,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'canonical_url' => $request->canonical_url,
            'custom_css' => $request->custom_css,
            'custom_js' => $request->custom_js,
            'blocks_data' => $request->blocks_data,
            'template_data' => $request->template_data,
            'is_homepage' => $request->boolean('is_homepage'),
            'is_blog_page' => $request->boolean('is_blog_page'),
            'is_contact_page' => $request->boolean('is_contact_page'),
            'is_landing_page' => $request->boolean('is_landing_page'),
            'parent_id' => $request->parent_id,
            'show_in_menu' => $request->boolean('show_in_menu'),
            'show_in_search' => $request->boolean('show_in_search'),
            'allow_comments' => $request->boolean('allow_comments'),
            'require_login' => $request->boolean('require_login'),
            'redirect_url' => $request->redirect_url,
            'redirect_type' => $request->redirect_type,
            'cache_duration' => $request->cache_duration,
            'is_cached' => $request->boolean('is_cached'),
            'published_at' => $request->published_at,
            'scheduled_at' => $request->scheduled_at,
            'expires_at' => $request->expires_at,
            'last_modified_at' => now(),
            'modified_by_id' => $user->id,
        ]);

        // Generate unique slug
        $page->generateSlug();

        // Calculate SEO score and read time
        $page->calculateReadTime();
        $page->getSeoScore();

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'status' => $page->status,
                'seo_score' => $page->seo_score,
            ],
        ], 201);
    }

    /**
     * Update page
     */
    public function update(Request $request, $id)
    {
        $page = AuraPage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'content' => 'sometimes|nullable|string',
            'template_id' => 'sometimes|nullable|exists:aura_templates,id',
            'status' => 'sometimes|required|in:draft,published,scheduled,archived',
            'visibility' => 'sometimes|required|in:public,private,password_protected',
            'password' => 'required_if:visibility,password_protected|string|min:6',
            'featured_image' => 'sometimes|nullable|url',
            'gallery_images' => 'sometimes|array',
            'meta_title' => 'sometimes|nullable|string|max:60',
            'meta_description' => 'sometimes|nullable|string|max:160',
            'meta_keywords' => 'sometimes|nullable|string',
            'canonical_url' => 'sometimes|nullable|url',
            'custom_css' => 'sometimes|nullable|string',
            'custom_js' => 'sometimes|nullable|string',
            'blocks_data' => 'sometimes|array',
            'template_data' => 'sometimes|array',
            'is_homepage' => 'sometimes|boolean',
            'is_blog_page' => 'sometimes|boolean',
            'is_contact_page' => 'sometimes|boolean',
            'is_landing_page' => 'sometimes|boolean',
            'parent_id' => 'sometimes|nullable|exists:aura_pages,id',
            'show_in_menu' => 'sometimes|boolean',
            'show_in_search' => 'sometimes|boolean',
            'allow_comments' => 'sometimes|boolean',
            'require_login' => 'sometimes|boolean',
            'redirect_url' => 'sometimes|nullable|url',
            'redirect_type' => 'sometimes|required_if:redirect_url|string|in:none,internal,external',
            'cache_duration' => 'sometimes|nullable|integer|min:60',
            'is_cached' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
            'scheduled_at' => 'sometimes|nullable|date|after:now',
            'expires_at' => 'sometimes|nullable|date|after:published_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check if homepage already exists
        if ($request->is_homepage && !$page->is_homepage) {
            AuraPage::where('is_homepage', true)->update(['is_homepage' => false]);
        }

        // Create revision before updating
        if ($request->has(['content', 'blocks_data', 'template_data'])) {
            $page->createRevision();
        }

        $page->update($request->except(['author_id', 'last_modified_at', 'modified_by_id']));
        $page->update([
            'last_modified_at' => now(),
            'modified_by_id' => $user->id,
        ]);

        // Regenerate slug if title changed
        if ($request->has('title') && $request->title !== $page->getOriginal('title')) {
            $page->generateSlug();
        }

        // Recalculate SEO score and read time
        $page->calculateReadTime();
        $page->getSeoScore();

        // Clear cache
        $page->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'status' => $page->status,
                'seo_score' => $page->seo_score,
            ],
        ]);
    }

    /**
     * Delete page
     */
    public function destroy($id)
    {
        $page = AuraPage::findOrFail($id);

        // Check if page has children
        if ($page->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete page with child pages. Please delete or move child pages first.',
            ], 400);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }

    /**
     * Duplicate page
     */
    public function duplicate($id)
    {
        $page = AuraPage::findOrFail($id);
        $newPage = $page->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Page duplicated successfully',
            'data' => [
                'id' => $newPage->id,
                'title' => $newPage->title,
                'slug' => $newPage->slug,
            ],
        ]);
    }

    /**
     * Publish page
     */
    public function publish($id)
    {
        $page = AuraPage::findOrFail($id);

        if ($page->publish()) {
            return response()->json([
                'success' => true,
                'message' => 'Page published successfully',
                'data' => [
                    'status' => $page->fresh()->status,
                    'published_at' => $page->fresh()->published_at,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to publish page',
        ], 500);
    }

    /**
     * Unpublish page
     */
    public function unpublish($id)
    {
        $page = AuraPage::findOrFail($id);

        if ($page->unpublish()) {
            return response()->json([
                'success' => true,
                'message' => 'Page unpublished successfully',
                'data' => [
                    'status' => $page->fresh()->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to unpublish page',
        ], 500);
    }

    /**
     * Schedule page
     */
    public function schedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $page = AuraPage::findOrFail($id);

        if ($page->schedule($request->scheduled_at)) {
            return response()->json([
                'success' => true,
                'message' => 'Page scheduled successfully',
                'data' => [
                    'status' => $page->fresh()->status,
                    'scheduled_at' => $page->fresh()->scheduled_at,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to schedule page',
        ], 500);
    }

    /**
     * Get page revisions
     */
    public function revisions($id)
    {
        $page = AuraPage::findOrFail($id);
        
        $revisions = $page->revisions()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $revisions->map(function ($revision) {
                return [
                    'id' => $revision->id,
                    'title' => $revision->title,
                    'revision_notes' => $revision->revision_notes,
                    'created_at' => $revision->created_at->format('Y-m-d H:i:s'),
                    'author' => [
                        'id' => $revision->author->id,
                        'name' => $revision->author->name,
                        'avatar' => $revision->author->avatar,
                    ],
                ];
            }),
            'pagination' => [
                'current_page' => $revisions->currentPage(),
                'last_page' => $revisions->lastPage(),
                'per_page' => $revisions->perPage(),
                'total' => $revisions->total(),
            ],
        ]);
    }

    /**
     * Restore revision
     */
    public function restoreRevision(Request $request, $id, $revisionId)
    {
        $page = AuraPage::findOrFail($id);

        if ($page->restoreRevision($revisionId)) {
            return response()->json([
                'success' => true,
                'message' => 'Revision restored successfully',
                'data' => [
                    'updated_at' => $page->fresh()->updated_at,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to restore revision',
        ], 500);
    }

    /**
     * Preview page
     */
    public function preview($id)
    {
        $page = AuraPage::findOrFail($id);

        $content = $page->getRenderedContent();

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $content,
                'title' => $page->title,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
        ]);
    }

    /**
     * Get page analytics
     */
    public function analytics($id, Request $request)
    {
        $page = AuraPage::findOrFail($id);
        
        $period = $request->get('period', '30days');
        $startDate = now()->subDays($period === '30days' ? 30 : ($period === '7days' ? 7 : 365));
        
        $analytics = $page->analytics()
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_views' => $page->view_count,
                'unique_visitors' => $analytics->sum('unique_visitors'),
                'avg_time_on_page' => $analytics->avg('avg_time_on_page'),
                'bounce_rate' => $analytics->avg('bounce_rate'),
                'conversions' => $analytics->sum('conversions'),
                'daily_stats' => $analytics->map(function ($stat) {
                    return [
                        'date' => $stat->date->format('Y-m-d'),
                        'views' => $stat->views,
                        'unique_visitors' => $stat->unique_visitors,
                        'avg_time_on_page' => $stat->avg_time_on_page,
                        'bounce_rate' => $stat->bounce_rate,
                        'conversions' => $stat->conversions,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get available templates
     */
    public function templates()
    {
        $templates = AuraTemplate::active()
            ->with(['reviews'])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'category' => $template->category,
                    'type' => $template->type,
                    'layout' => $template->layout,
                    'preview_image' => $template->preview_image,
                    'is_premium' => $template->is_premium,
                    'price' => $template->price,
                    'rating' => $template->rating,
                    'reviews_count' => $template->reviews_count,
                    'usage_count' => $template->usage_count,
                    'is_featured' => $template->is_featured,
                ];
            }),
        ]);
    }

    /**
     * Get page builder blocks
     */
    public function blocks()
    {
        $blocks = [
            'heading' => [
                'name' => 'Heading',
                'icon' => 'heading',
                'category' => 'text',
                'config' => [
                    'level' => ['type' => 'select', 'options' => [1, 2, 3, 4, 5, 6], 'default' => 1],
                    'text' => ['type' => 'text', 'default' => 'Heading Text'],
                    'alignment' => ['type' => 'select', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'text' => [
                'name' => 'Text',
                'icon' => 'text',
                'category' => 'text',
                'config' => [
                    'content' => ['type' => 'textarea', 'default' => 'Text content'],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'image' => [
                'name' => 'Image',
                'icon' => 'image',
                'category' => 'media',
                'config' => [
                    'src' => ['type' => 'image', 'default' => ''],
                    'alt' => ['type' => 'text', 'default' => ''],
                    'width' => ['type' => 'text', 'default' => 'auto'],
                    'height' => ['type' => 'text', 'default' => 'auto'],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'video' => [
                'name' => 'Video',
                'icon' => 'video',
                'category' => 'media',
                'config' => [
                    'url' => ['type' => 'text', 'default' => ''],
                    'type' => ['type' => 'select', 'options' => ['iframe', 'video'], 'default' => 'iframe'],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'button' => [
                'name' => 'Button',
                'icon' => 'button',
                'category' => 'actions',
                'config' => [
                    'text' => ['type' => 'text', 'default' => 'Button'],
                    'url' => ['type' => 'text', 'default' => '#'],
                    'style' => ['type' => 'select', 'options' => ['primary', 'secondary', 'success', 'danger'], 'default' => 'primary'],
                    'size' => ['type' => 'select', 'options' => ['sm', 'md', 'lg'], 'default' => 'md'],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'form' => [
                'name' => 'Form',
                'icon' => 'form',
                'category' => 'actions',
                'config' => [
                    'formId' => ['type' => 'select', 'options' => [], 'default' => ''],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'gallery' => [
                'name' => 'Gallery',
                'icon' => 'gallery',
                'category' => 'media',
                'config' => [
                    'images' => ['type' => 'array', 'default' => []],
                    'columns' => ['type' => 'select', 'options' => [1, 2, 3, 4, 5, 6], 'default' => 3],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'testimonial' => [
                'name' => 'Testimonial',
                'icon' => 'testimonial',
                'category' => 'content',
                'config' => [
                    'quote' => ['type' => 'textarea', 'default' => ''],
                    'author' => ['type' => 'text', 'default' => ''],
                    'role' => ['type' => 'text', 'default' => ''],
                    'avatar' => ['type' => 'image', 'default' => ''],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'pricing' => [
                'name' => 'Pricing',
                'icon' => 'pricing',
                'category' => 'content',
                'config' => [
                    'plans' => ['type' => 'array', 'default' => []],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'contact' => [
                'name' => 'Contact',
                'icon' => 'contact',
                'category' => 'content',
                'config' => [
                    'email' => ['type' => 'text', 'default' => ''],
                    'phone' => ['type' => 'text', 'default' => ''],
                    'address' => ['type' => 'text', 'default' => ''],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'hero' => [
                'name' => 'Hero',
                'icon' => 'hero',
                'category' => 'layout',
                'config' => [
                    'title' => ['type' => 'text', 'default' => 'Hero Title'],
                    'subtitle' => ['type' => 'text', 'default' => 'Hero Subtitle'],
                    'backgroundImage' => ['type' => 'image', 'default' => ''],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'features' => [
                'name' => 'Features',
                'icon' => 'features',
                'category' => 'content',
                'config' => [
                    'features' => ['type' => 'array', 'default' => []],
                    'columns' => ['type' => 'select', 'options' => [1, 2, 3, 4], 'default' => 3],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'stats' => [
                'name' => 'Stats',
                'icon' => 'stats',
                'category' => 'content',
                'config' => [
                    'stats' => ['type' => 'array', 'default' => []],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'team' => [
                'name' => 'Team',
                'icon' => 'team',
                'category' => 'content',
                'config' => [
                    'members' => ['type' => 'array', 'default' => []],
                    'columns' => ['type' => 'select', 'options' => [1, 2, 3, 4, 5], 'default' => 4],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'faq' => [
                'name' => 'FAQ',
                'icon' => 'faq',
                'category' => 'content',
                'config' => [
                    'faqs' => ['type' => 'array', 'default' => []],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
            'custom' => [
                'name' => 'Custom HTML',
                'icon' => 'code',
                'category' => 'advanced',
                'config' => [
                    'html' => ['type' => 'textarea', 'default' => ''],
                    'className' => ['type' => 'text', 'default' => ''],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $blocks,
        ]);
    }
}
