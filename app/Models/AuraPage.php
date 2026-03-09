<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AuraPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'template_id',
        'layout',
        'status', // 'draft', 'published', 'scheduled', 'archived'
        'visibility', // 'public', 'private', 'password_protected'
        'password',
        'author_id',
        'featured_image',
        'gallery_images',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'og_title',
        'og_description',
        'canonical_url',
        'schema_data',
        'custom_css',
        'custom_js',
        'header_scripts',
        'footer_scripts',
        'blocks_data',
        'template_data',
        'global_blocks',
        'dynamic_content',
        'form_data',
        'seo_score',
        'read_time',
        'word_count',
        'published_at',
        'scheduled_at',
        'expires_at',
        'last_modified_at',
        'modified_by_id',
        'version',
        'is_homepage',
        'is_blog_page',
        'is_contact_page',
        'is_landing_page',
        'parent_id',
        'sort_order',
        'menu_order',
        'show_in_menu',
        'show_in_search',
        'allow_comments',
        'comment_status',
        'require_login',
        'login_redirect_url',
        'redirect_url',
        'redirect_type', // 'none', 'internal', 'external'
        'cache_duration',
        'is_cached',
        'cache_key',
        'view_count',
        'conversion_goal',
        'conversion_tracking',
        'a_b_test_id',
        'language',
        'translations',
        'tags',
        'categories',
        'custom_fields',
        'settings',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_modified_at' => 'datetime',
        'gallery_images' => 'array',
        'blocks_data' => 'array',
        'template_data' => 'array',
        'global_blocks' => 'array',
        'dynamic_content' => 'array',
        'form_data' => 'array',
        'schema_data' => 'array',
        'seo_score' => 'integer',
        'read_time' => 'integer',
        'word_count' => 'integer',
        'is_homepage' => 'boolean',
        'is_blog_page' => 'boolean',
        'is_contact_page' => 'boolean',
        'is_landing_page' => 'boolean',
        'show_in_menu' => 'boolean',
        'show_in_search' => 'boolean',
        'allow_comments' => 'boolean',
        'require_login' => 'boolean',
        'is_cached' => 'boolean',
        'conversion_tracking' => 'array',
        'translations' => 'array',
        'tags' => 'array',
        'categories' => 'array',
        'custom_fields' => 'array',
        'settings' => 'array',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by_id');
    }

    public function template()
    {
        return $this->belongsTo(AuraTemplate::class);
    }

    public function parent()
    {
        return $this->belongsTo(AuraPage::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AuraPage::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    public function revisions()
    {
        return $this->hasMany(AuraPageRevision::class);
    }

    public function analytics()
    {
        return $this->hasMany(AuraPageAnalytics::class);
    }

    public function comments()
    {
        return $this->hasMany(AuraComment::class);
    }

    public function forms()
    {
        return $this->hasMany(AuraForm::class);
    }

    public function menus()
    {
        return $this->belongsToMany(AuraMenu::class, 'aura_menu_items');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeVisible($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeSearchable($query)
    {
        return $query->where('show_in_search', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // Methods
    public function generateSlug()
    {
        $slug = Str::slug($this->title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $this->slug = $slug;
        $this->save();

        return $slug;
    }

    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'last_modified_at' => now(),
            'modified_by_id' => auth()->id(),
        ]);

        // Clear cache
        $this->clearCache();

        return true;
    }

    public function unpublish()
    {
        $this->update([
            'status' => 'draft',
            'last_modified_at' => now(),
            'modified_by_id' => auth()->id(),
        ]);

        // Clear cache
        $this->clearCache();

        return true;
    }

    public function schedule($dateTime)
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $dateTime,
            'last_modified_at' => now(),
            'modified_by_id' => auth()->id(),
        ]);

        return true;
    }

    public function archive()
    {
        $this->update([
            'status' => 'archived',
            'last_modified_at' => now(),
            'modified_by_id' => auth()->id(),
        ]);

        // Clear cache
        $this->clearCache();

        return true;
    }

    public function duplicate()
    {
        $newPage = $this->replicate();
        $newPage->title = $this->title . ' (Copy)';
        $newPage->slug = null;
        $newPage->status = 'draft';
        $newPage->published_at = null;
        $newPage->view_count = 0;
        $newPage->is_homepage = false;
        $newPage->save();

        $newPage->generateSlug();

        return $newPage;
    }

    public function createRevision()
    {
        return $this->revisions()->create([
            'title' => $this->title,
            'content' => $this->content,
            'blocks_data' => $this->blocks_data,
            'template_data' => $this->template_data,
            'custom_css' => $this->custom_css,
            'custom_js' => $this->custom_js,
            'author_id' => auth()->id(),
            'revision_notes' => 'Auto-save revision',
        ]);
    }

    public function restoreRevision($revisionId)
    {
        $revision = $this->revisions()->findOrFail($revisionId);

        $this->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'blocks_data' => $revision->blocks_data,
            'template_data' => $revision->template_data,
            'custom_css' => $revision->custom_css,
            'custom_js' => $revision->custom_js,
            'last_modified_at' => now(),
            'modified_by_id' => auth()->id(),
        ]);

        // Create new revision
        $this->createRevision();

        return true;
    }

    public function getRenderedContent()
    {
        if ($this->is_cached && $this->cache_key) {
            $cached = cache()->get($this->cache_key);
            if ($cached) {
                return $cached;
            }
        }

        // Render blocks
        $content = $this->renderBlocks();

        // Apply template
        if ($this->template) {
            $content = $this->template->render($content, $this->template_data);
        }

        // Cache if enabled
        if ($this->is_cached) {
            $cacheKey = 'aura_page_' . $this->id . '_' . md5($this->updated_at->timestamp);
            $this->update(['cache_key' => $cacheKey]);
            cache()->put($cacheKey, $content, $this->cache_duration ?? 3600);
        }

        return $content;
    }

    public function renderBlocks()
    {
        if (!$this->blocks_data || empty($this->blocks_data)) {
            return $this->content;
        }

        $html = '';
        
        foreach ($this->blocks_data as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    public function renderBlock($block)
    {
        $blockType = $block['type'] ?? 'text';
        $blockData = $block['data'] ?? [];

        switch ($blockType) {
            case 'heading':
                return $this->renderHeadingBlock($blockData);
            case 'text':
                return $this->renderTextBlock($blockData);
            case 'image':
                return $this->renderImageBlock($blockData);
            case 'video':
                return $this->renderVideoBlock($blockData);
            case 'button':
                return $this->renderButtonBlock($blockData);
            case 'form':
                return $this->renderFormBlock($blockData);
            case 'gallery':
                return $this->renderGalleryBlock($blockData);
            case 'testimonial':
                return $this->renderTestimonialBlock($blockData);
            case 'pricing':
                return $this->renderPricingBlock($blockData);
            case 'contact':
                return $this->renderContactBlock($blockData);
            case 'hero':
                return $this->renderHeroBlock($blockData);
            case 'features':
                return $this->renderFeaturesBlock($blockData);
            case 'stats':
                return $this->renderStatsBlock($blockData);
            case 'team':
                return $this->renderTeamBlock($blockData);
            case 'faq':
                return $this->renderFaqBlock($blockData);
            case 'custom':
                return $this->renderCustomBlock($blockData);
            default:
                return '';
        }
    }

    private function renderHeadingBlock($data)
    {
        $level = $data['level'] ?? 1;
        $text = $data['text'] ?? '';
        $alignment = $data['alignment'] ?? 'left';
        $className = $data['className'] ?? '';

        return "<h{$level} class=\"text-{$alignment} {$className}\">{$text}</h{$level}>";
    }

    private function renderTextBlock($data)
    {
        $content = $data['content'] ?? '';
        $className = $data['className'] ?? '';

        return "<div class=\"{$className}\">{$content}</div>";
    }

    private function renderImageBlock($data)
    {
        $src = $data['src'] ?? '';
        $alt = $data['alt'] ?? '';
        $width = $data['width'] ?? 'auto';
        $height = $data['height'] ?? 'auto';
        $className = $data['className'] ?? '';

        return "<img src=\"{$src}\" alt=\"{$alt}\" style=\"width: {$width}; height: {$height};\" class=\"{$className}\">";
    }

    private function renderVideoBlock($data)
    {
        $url = $data['url'] ?? '';
        $type = $data['type'] ?? 'iframe';
        $className = $data['className'] ?? '';

        if ($type === 'iframe') {
            return "<iframe src=\"{$url}\" class=\"{$className}\" frameborder=\"0\" allowfullscreen></iframe>";
        }

        return "<video src=\"{$url}\" class=\"{$className}\" controls></video>";
    }

    private function renderButtonBlock($data)
    {
        $text = $data['text'] ?? '';
        $url = $data['url'] ?? '#';
        $style = $data['style'] ?? 'primary';
        $size = $data['size'] ?? 'md';
        $className = $data['className'] ?? '';

        $styleClasses = [
            'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
            'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
            'success' => 'bg-green-600 text-white hover:bg-green-700',
            'danger' => 'bg-red-600 text-white hover:bg-red-700',
        ];

        $sizeClasses = [
            'sm' => 'px-3 py-1 text-sm',
            'md' => 'px-4 py-2 text-base',
            'lg' => 'px-6 py-3 text-lg',
        ];

        $classes = "{$styleClasses[$style]} {$sizeClasses[$size]} {$className}";

        return "<a href=\"{$url}\" class=\"{$classes}\">{$text}</a>";
    }

    private function renderFormBlock($data)
    {
        $formId = $data['formId'] ?? '';
        $className = $data['className'] ?? '';

        if ($formId) {
            $form = AuraForm::find($formId);
            if ($form) {
                return $form->render($className);
            }
        }

        return "<!-- Form Block: Form ID {$formId} not found -->";
    }

    private function renderGalleryBlock($data)
    {
        $images = $data['images'] ?? [];
        $columns = $data['columns'] ?? 3;
        $className = $data['className'] ?? '';

        $html = "<div class=\"grid grid-cols-{$columns} gap-4 {$className}\">";
        
        foreach ($images as $image) {
            $html .= "<img src=\"{$image['src']}\" alt=\"{$image['alt'] ?? ''}\" class=\"w-full h-auto\">";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderTestimonialBlock($data)
    {
        $quote = $data['quote'] ?? '';
        $author = $data['author'] ?? '';
        $role = $data['role'] ?? '';
        $avatar = $data['avatar'] ?? '';
        $className = $data['className'] ?? '';

        return "
            <div class=\"testimonial {$className}\">
                <blockquote class=\"text-lg italic\">{$quote}</blockquote>
                <div class=\"flex items-center mt-4\">
                    <img src=\"{$avatar}\" alt=\"{$author}\" class=\"w-12 h-12 rounded-full mr-4\">
                    <div>
                        <p class=\"font-semibold\">{$author}</p>
                        <p class=\"text-sm text-gray-600\">{$role}</p>
                    </div>
                </div>
            </div>
        ";
    }

    private function renderPricingBlock($data)
    {
        $plans = $data['plans'] ?? [];
        $className = $data['className'] ?? '';

        $html = "<div class=\"pricing-grid grid grid-cols-1 md:grid-cols-{$count($plans)} gap-6 {$className}\">";

        foreach ($plans as $plan) {
            $featured = $plan['featured'] ?? false;
            $featuredClass = $featured ? 'ring-2 ring-blue-500 transform scale-105' : '';
            
            $html .= "
                <div class=\"pricing-card {$featuredClass} {$className}\">
                    <h3 class=\"text-xl font-bold\">{$plan['name']}</h3>
                    <p class=\"text-3xl font-bold\">{$plan['price']}</p>
                    <ul class=\"mt-4 space-y-2\">
            ";

            foreach ($plan['features'] as $feature) {
                $html .= "<li class=\"flex items-center\"><span class=\"text-green-500 mr-2\">✓</span> {$feature}</li>";
            }

            $html .= "
                    </ul>
                    <button class=\"mt-6 w-full bg-blue-600 text-white py-2 rounded\">{$plan['buttonText']}</button>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderContactBlock($data)
    {
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $className = $data['className'] ?? '';

        return "
            <div class=\"contact-info {$className}\">
                <div class=\"mb-4\">
                    <h4>Email</h4>
                    <p>{$email}</p>
                </div>
                <div class=\"mb-4\">
                    <h4>Phone</h4>
                    <p>{$phone}</p>
                </div>
                <div>
                    <h4>Address</h4>
                    <p>{$address}</p>
                </div>
            </div>
        ";
    }

    private function renderHeroBlock($data)
    {
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $backgroundImage = $data['backgroundImage'] ?? '';
        $className = $data['className'] ?? '';

        $style = $backgroundImage ? "background-image: url('{$backgroundImage}'); background-size: cover; background-position: center;" : '';

        return "
            <section class=\"hero {$className}\" style=\"{$style}\">
                <div class=\"container mx-auto px-4 py-16 text-center\">
                    <h1 class=\"text-4xl md:text-6xl font-bold mb-4\">{$title}</h1>
                    <p class=\"text-xl md:text-2xl mb-8\">{$subtitle}</p>
                </div>
            </section>
        ";
    }

    private function renderFeaturesBlock($data)
    {
        $features = $data['features'] ?? [];
        $columns = $data['columns'] ?? 3;
        $className = $data['className'] ?? '';

        $html = "<div class=\"features grid grid-cols-1 md:grid-cols-{$columns} gap-8 {$className}\">";

        foreach ($features as $feature) {
            $html .= "
                <div class=\"feature text-center\">
                    <div class=\"icon mb-4\">{$feature['icon']}</div>
                    <h3 class=\"text-xl font-semibold mb-2\">{$feature['title']}</h3>
                    <p class=\"text-gray-600\">{$feature['description']}</p>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderStatsBlock($data)
    {
        $stats = $data['stats'] ?? [];
        $className = $data['className'] ?? '';

        $html = "<div class=\"stats grid grid-cols-1 md:grid-cols-" . count($stats) . " gap-8 {$className}\">";

        foreach ($stats as $stat) {
            $html .= "
                <div class=\"stat text-center\">
                    <div class=\"text-4xl font-bold text-blue-600\">{$stat['value']}</div>
                    <div class=\"text-gray-600 mt-2\">{$stat['label']}</div>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderTeamBlock($data)
    {
        $members = $data['members'] ?? [];
        $columns = $data['columns'] ?? 4;
        $className = $data['className'] ?? '';

        $html = "<div class=\"team grid grid-cols-1 md:grid-cols-{$columns} gap-8 {$className}\">";

        foreach ($members as $member) {
            $html .= "
                <div class=\"team-member text-center\">
                    <img src=\"{$member['photo']}\" alt=\"{$member['name']}\" class=\"w-32 h-32 rounded-full mx-auto mb-4\">
                    <h3 class=\"text-xl font-semibold\">{$member['name']}</h3>
                    <p class=\"text-gray-600\">{$member['role']}</p>
                    <p class=\"text-sm mt-2\">{$member['bio']}</p>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderFaqBlock($data)
    {
        $faqs = $data['faqs'] ?? [];
        $className = $data['className'] ?? '';

        $html = "<div class=\"faq {$className}\">";

        foreach ($faqs as $index => $faq) {
            $html .= "
                <div class=\"faq-item border-b mb-4\">
                    <button class=\"faq-question w-full text-left py-2 font-semibold\" onclick=\"toggleFAQ({$index})\">
                        {$faq['question']}
                    </button>
                    <div class=\"faq-answer hidden py-2 text-gray-600\" id=\"faq-answer-{$index}\">
                        {$faq['answer']}
                    </div>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderCustomBlock($data)
    {
        $html = $data['html'] ?? '';
        $className = $data['className'] ?? '';

        return "<div class=\"custom-block {$className}\">{$html}</div>";
    }

    public function clearCache()
    {
        if ($this->cache_key) {
            cache()->forget($this->cache_key);
            $this->update(['cache_key' => null]);
        }
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function calculateReadTime()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute

        $this->update([
            'word_count' => $wordCount,
            'read_time' => $readTime,
        ]);

        return $readTime;
    }

    public function getSeoScore()
    {
        $score = 0;

        // Title length check
        if (strlen($this->title) >= 30 && strlen($this->title) <= 60) {
            $score += 20;
        }

        // Meta description check
        if ($this->meta_description && strlen($this->meta_description) >= 120 && strlen($this->meta_description) <= 160) {
            $score += 20;
        }

        // Content length check
        if ($this->word_count >= 300) {
            $score += 20;
        }

        // Image alt text check
        if ($this->featured_image) {
            $score += 10;
        }

        // Heading structure check
        if (preg_match('/<h1/', $this->content)) {
            $score += 10;
        }

        // Internal links check
        if (preg_match('/href="\/[^"]*"/', $this->content)) {
            $score += 10;
        }

        // Meta keywords check
        if ($this->meta_keywords) {
            $score += 10;
        }

        $this->update(['seo_score' => $score]);

        return $score;
    }

    public function getSchemaData()
    {
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'WebPage',
            'name' => $this->title,
            'description' => $this->description ?: $this->meta_description,
            'url' => route('aura.pages.show', $this->slug),
            'datePublished' => $this->published_at?->toISOString(),
            'dateModified' => $this->last_modified_at?->toISOString(),
            'author' => [
                '@type' => 'Person',
                'name' => $this->author->name,
            ],
        ];

        if ($this->featured_image) {
            $schema['image'] = $this->featured_image;
        }

        return $schema;
    }

    public function canBeAccessedBy($user)
    {
        if ($this->visibility === 'public') {
            return true;
        }

        if ($this->visibility === 'private' && $user) {
            return true;
        }

        if ($this->visibility === 'password_protected') {
            return session()->has('aura_page_password_' . $this->id);
        }

        return false;
    }

    public function addCustomField($key, $value)
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        
        $this->custom_fields = $fields;
        $this->save();
        
        return true;
    }

    public function getCustomField($key, $default = null)
    {
        $fields = $this->custom_fields ?? [];
        
        return $fields[$key] ?? $default;
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = collect();
        $current = $this;

        while ($current) {
            $breadcrumbs->prepend([
                'title' => $current->title,
                'url' => route('aura.pages.show', $current->slug),
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    public function getChildren()
    {
        return $this->children()->published()->ordered()->get();
    }

    public function getSiblings()
    {
        if ($this->parent_id) {
            return static::where('parent_id', $this->parent_id)
                ->where('id', '!=', $this->id)
                ->published()
                ->ordered()
                ->get();
        }

        return static::whereNull('parent_id')
            ->where('id', '!=', $this->id)
            ->published()
            ->ordered()
            ->get();
    }
}
