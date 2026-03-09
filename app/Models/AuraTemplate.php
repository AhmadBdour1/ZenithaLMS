<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuraTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category', // 'business', 'portfolio', 'blog', 'ecommerce', 'landing', 'custom'
        'type', // 'page', 'section', 'block', 'component'
        'layout', // 'default', 'full-width', 'sidebar-left', 'sidebar-right', 'grid'
        'preview_image',
        'thumbnail',
        'html_structure',
        'css_styles',
        'js_scripts',
        'variables',
        'sections',
        'blocks',
        'components',
        'global_styles',
        'responsive_rules',
        'animation_settings',
        'color_scheme',
        'typography',
        'spacing',
        'borders',
        'shadows',
        'header_config',
        'footer_config',
        'sidebar_config',
        'navigation_config',
        'form_styles',
        'button_styles',
        'card_styles',
        'table_styles',
        'is_premium',
        'price',
        'usage_count',
        'rating',
        'reviews_count',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_data',
    ];

    protected $casts = [
        'html_structure' => 'array',
        'css_styles' => 'array',
        'js_scripts' => 'array',
        'variables' => 'array',
        'sections' => 'array',
        'blocks' => 'array',
        'components' => 'array',
        'global_styles' => 'array',
        'responsive_rules' => 'array',
        'animation_settings' => 'array',
        'color_scheme' => 'array',
        'typography' => 'array',
        'spacing' => 'array',
        'borders' => 'array',
        'shadows' => 'array',
        'header_config' => 'array',
        'footer_config' => 'array',
        'sidebar_config' => 'array',
        'navigation_config' => 'array',
        'form_styles' => 'array',
        'button_styles' => 'array',
        'card_styles' => 'array',
        'table_styles' => 'array',
        'is_premium' => 'boolean',
        'price' => 'decimal:2',
        'usage_count' => 'integer',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'meta_data' => 'array',
    ];

    // Relationships
    public function pages()
    {
        return $this->hasMany(AuraPage::class);
    }

    public function reviews()
    {
        return $this->hasMany(AuraTemplateReview::class);
    }

    public function purchases()
    {
        return $this->hasMany(AuraTemplatePurchase::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function scopeHighestRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    // Methods
    public function render($content, $variables = [])
    {
        $html = $this->getHtmlStructure();
        
        // Replace variables
        $html = $this->replaceVariables($html, $variables);
        
        // Insert content
        $html = str_replace('{{content}}', $content, $html);
        
        // Insert styles
        $styles = $this->getCompiledStyles();
        $html = str_replace('{{styles}}', $styles, $html);
        
        // Insert scripts
        $scripts = $this->getCompiledScripts();
        $html = str_replace('{{scripts}}', $scripts, $html);
        
        return $html;
    }

    public function getHtmlStructure()
    {
        if ($this->html_structure) {
            return $this->buildHtmlFromStructure($this->html_structure);
        }

        return $this->getDefaultHtmlStructure();
    }

    private function buildHtmlFromStructure($structure)
    {
        $html = '';

        foreach ($structure as $element) {
            $html .= $this->buildElement($element);
        }

        return $html;
    }

    private function buildElement($element)
    {
        $tag = $element['tag'] ?? 'div';
        $attributes = $element['attributes'] ?? [];
        $children = $element['children'] ?? [];
        $content = $element['content'] ?? '';

        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }

        $html = "<{$tag}{$attrString}>";

        if (!empty($children)) {
            foreach ($children as $child) {
                $html .= $this->buildElement($child);
            }
        } else {
            $html .= $content;
        }

        $html .= "</{$tag}>";

        return $html;
    }

    private function getDefaultHtmlStructure()
    {
        return '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{title}}</title>
                {{styles}}
                {{scripts}}
            </head>
            <body>
                <header class="site-header">
                    {{header}}
                </header>
                <main class="site-main">
                    {{content}}
                </main>
                <footer class="site-footer">
                    {{footer}}
                </footer>
            </body>
            </html>
        ';
    }

    private function replaceVariables($html, $variables)
    {
        foreach ($variables as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return $html;
    }

    public function getCompiledStyles()
    {
        $styles = [];

        // Global styles
        if ($this->global_styles) {
            $styles[] = $this->compileStyles($this->global_styles);
        }

        // Component styles
        if ($this->components) {
            foreach ($this->components as $component) {
                if (isset($component['styles'])) {
                    $styles[] = $this->compileStyles($component['styles'], $component['class']);
                }
            }
        }

        // Custom CSS
        if ($this->css_styles) {
            $styles[] = $this->compileStyles($this->css_styles);
        }

        return '<style>' . implode("\n", $styles) . '</style>';
    }

    private function compileStyles($styles, $prefix = '')
    {
        $css = '';

        foreach ($styles as $property => $value) {
            if (is_array($value)) {
                // Nested styles
                $nestedPrefix = $prefix ? $prefix . ' ' . $property : $property;
                $css .= $this->compileStyles($value, $nestedPrefix);
            } else {
                $selector = $prefix ?: 'body';
                $css .= "{$selector} { {$property}: {$value}; }\n";
            }
        }

        return $css;
    }

    public function getCompiledScripts()
    {
        $scripts = [];

        // Global scripts
        if ($this->js_scripts) {
            $scripts[] = '<script>' . $this->js_scripts . '</script>';
        }

        // Component scripts
        if ($this->components) {
            foreach ($this->components as $component) {
                if (isset($component['script'])) {
                    $scripts[] = '<script>' . $component['script'] . '</script>';
                }
            }
        }

        return implode("\n", $scripts);
    }

    public function getSection($sectionName)
    {
        if ($this->sections && isset($this->sections[$sectionName])) {
            return $this->sections[$sectionName];
        }

        return $this->getDefaultSection($sectionName);
    }

    private function getDefaultSection($sectionName)
    {
        $defaults = [
            'header' => '
                <nav class="navbar">
                    <div class="container">
                        <a href="/" class="logo">{{site_name}}</a>
                        <ul class="nav-menu">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about">About</a></li>
                            <li><a href="/contact">Contact</a></li>
                        </ul>
                    </div>
                </nav>
            ',
            'footer' => '
                <footer class="site-footer">
                    <div class="container">
                        <p>&copy; {{year}} {{site_name}}. All rights reserved.</p>
                    </div>
                </footer>
            ',
        ];

        return $defaults[$sectionName] ?? '';
    }

    public function getBlock($blockName)
    {
        if ($this->blocks && isset($this->blocks[$blockName])) {
            return $this->blocks[$blockName];
        }

        return null;
    }

    public function getComponent($componentName)
    {
        if ($this->components && isset($this->components[$componentName])) {
            return $this->components[$componentName];
        }

        return null;
    }

    public function addSection($name, $content)
    {
        $sections = $this->sections ?? [];
        $sections[$name] = $content;
        
        $this->sections = $sections;
        $this->save();
        
        return true;
    }

    public function addBlock($name, $content)
    {
        $blocks = $this->blocks ?? [];
        $blocks[$name] = $content;
        
        $this->blocks = $blocks;
        $this->save();
        
        return true;
    }

    public function addComponent($name, $component)
    {
        $components = $this->components ?? [];
        $components[$name] = $component;
        
        $this->components = $components;
        $this->save();
        
        return true;
    }

    public function updateVariable($key, $value)
    {
        $variables = $this->variables ?? [];
        $variables[$key] = $value;
        
        $this->variables = $variables;
        $this->save();
        
        return true;
    }

    public function getVariable($key, $default = null)
    {
        $variables = $this->variables ?? [];
        
        return $variables[$key] ?? $default;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function getAverageRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function updateRating()
    {
        $this->rating = $this->getAverageRating();
        $this->reviews_count = $this->reviews()->count();
        $this->save();
    }

    public function canBeUsedBy($user)
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->is_premium) {
            return true;
        }

        // Check if user has premium subscription or purchased template
        return $user->hasSubscription('premium') || $this->purchases()->where('user_id', $user->id)->exists();
    }

    public function duplicate()
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $this->name . ' (Copy)';
        $newTemplate->slug = $this->slug . '-copy-' . time();
        $newTemplate->usage_count = 0;
        $newTemplate->rating = 0;
        $newTemplate->reviews_count = 0;
        $newTemplate->save();

        return $newTemplate;
    }

    public function export()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,
            'layout' => $this->layout,
            'html_structure' => $this->html_structure,
            'css_styles' => $this->css_styles,
            'js_scripts' => $this->js_scripts,
            'variables' => $this->variables,
            'sections' => $this->sections,
            'blocks' => $this->blocks,
            'components' => $this->components,
            'global_styles' => $this->global_styles,
            'color_scheme' => $this->color_scheme,
            'typography' => $this->typography,
            'version' => '1.0.0',
            'exported_at' => now()->toISOString(),
        ];
    }

    public function import($data)
    {
        $this->update([
            'html_structure' => $data['html_structure'] ?? $this->html_structure,
            'css_styles' => $data['css_styles'] ?? $this->css_styles,
            'js_scripts' => $data['js_scripts'] ?? $this->js_scripts,
            'variables' => $data['variables'] ?? $this->variables,
            'sections' => $data['sections'] ?? $this->sections,
            'blocks' => $data['blocks'] ?? $this->blocks,
            'components' => $data['components'] ?? $this->components,
            'global_styles' => $data['global_styles'] ?? $this->global_styles,
            'color_scheme' => $data['color_scheme'] ?? $this->color_scheme,
            'typography' => $data['typography'] ?? $this->typography,
        ]);

        return true;
    }

    public static function getPopularTemplates($limit = 10)
    {
        return static::active()->popular()->take($limit)->get();
    }

    public static function getFreeTemplates()
    {
        return static::active()->free()->orderBy('sort_order')->get();
    }

    public static function getPremiumTemplates()
    {
        return static::active()->premium()->orderBy('sort_order')->get();
    }

    public static function getByCategory($category)
    {
        return static::active()->byCategory($category)->orderBy('sort_order')->get();
    }

    public static function search($query)
    {
        return static::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderBy('sort_order')
            ->get();
    }
}
