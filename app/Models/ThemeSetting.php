<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'theme_id',
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public',
        'is_editable',
        'validation_rules',
        'default_value',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_editable' => 'boolean',
        'validation_rules' => 'array',
        'default_value' => 'json',
    ];

    /**
     * ZenithaLMS: Setting Types
     */
    const TYPE_COLOR = 'color';
    const TYPE_FONT = 'font';
    const TYPE_SIZE = 'size';
    const TYPE_SPACING = 'spacing';
    const TYPE_BORDER = 'border';
    const TYPE_SHADOW = 'shadow';
    const TYPE_ANIMATION = 'animation';
    const TYPE_IMAGE = 'image';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';

    /**
     * ZenithaLMS: Setting Categories
     */
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_COLORS = 'colors';
    const CATEGORY_FONTS = 'fonts';
    const CATEGORY_LAYOUT = 'layout';
    const COMPONENTS = 'components';
    const NAVIGATION = 'navigation';
    const_CONTENT = 'content';
    const_FORMS = 'forms';
    const_BUTTONS = 'buttons';
    const_CARDS = 'cards';

    /**
     * ZenithaLMS: Relationships
     */
    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByTheme($query, $themeId)
    {
        return $query->where('theme_id', $themeId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * ZenithLMS: Methods
     */
    public function isPublic()
    {
        return $this->is_public;
    }

    public function isEditable()
    {
        return $this->is_editable;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getKey()
    {
        return $key;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getValidationRules()
    {
        return $this->validation_rules ?? [];
    }

    public function getDefaultValue()
    {
        return $this->default_value;
    }

    public function getFormattedValue()
    {
        switch ($this->type) {
            case self::TYPE_COLOR:
                return $this->formatColorValue();
            case self::TYPE_FONT:
                return $this->formatFontValue();
            case self::TYPE_SIZE:
                return $this->formatSizeValue();
            case self::TYPE_BOOLEAN:
                return $this->formatBooleanValue();
            case self::TYPE_NUMBER:
                return $this->formatNumberValue();
            default:
                return $this->value;
        }
    }

    public function formatColorValue()
    {
        $value = $this->value;
        
        // Ensure it's a valid hex color
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $value = '#' . ltrim($value, '#');
        }
        
        return $value;
    }

    public function formatFontValue()
    {
        return $this->value;
    }

    public function formatSizeValue()
    {
        return $this->value . 'px';
    }

    public function formatBooleanValue()
    {
        return $this->value ? 'true' : 'false';
    }

    public function formatNumberValue()
    {
        return number_format($this->value, 2);
    }

    public function getCssVariable()
    {
        return '--' . str_replace('_', '-', $this->key) . ': ' . $this->getFormattedValue();
    }

    public function getCssProperty()
    {
        return $this->key;
    }

    /**
     * ZenithaLMS: Validation Methods
     */
    public function validateValue($value)
    {
        $rules = $this->getValidationRules();
        
        if (empty($rules)) {
            return true;
        }
        
        switch ($this->type) {
            case self::TYPE_COLOR:
                return $this->validateColor($value);
            case self::TYPE_FONT:
                return $this->validateFont($value);
            case self::TYPE_SIZE:
                return $this->validateSize($value);
            case self::TYPE_BOOLEAN:
                return $this->validateBoolean($value);
            case self::TYPE_NUMBER:
                return $this->validateNumber($value);
            case self::TYPE_TEXT:
                return $this->validateText($value);
            default:
                return true;
        }
    }

    private function validateColor($value)
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }

    private function validateFont($value)
    {
        $validFonts = [
            'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana',
            'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
            'Nunito', 'Source Sans Pro', 'Fira Sans', 'Ubuntu', 'Cantarell',
        ];
        
        return in_array($value, $validFonts);
    }

    private function validateSize($value)
    {
        return is_numeric($value) && $value >= 0 && $value <= 100;
    }

    private function validateBoolean($value)
    {
        return in_array(strtolower($value), ['true', 'false', '1', '0']);
    }

    private function validateNumber($value)
    {
        return is_numeric($value);
    }

    private function validateText($value)
    {
        return is_string($value) && strlen($value) <= 255;
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiRecommendation()
    {
        // ZenithaLMS: Generate AI-powered setting recommendations
        $recommendation = [
            'suggested_value' => $this->suggestOptimalValue(),
            'usage_context' => $this->analyzeUsageContext(),
            'user_preference' => $this->predictUserPreference(),
            'accessibility_score' => $this->calculateAccessibilityScore($value),
            'performance_impact' => $this->calculatePerformanceImpact($value),
            'aesthetic_score' => $this->calculateAestheticScore($value),
            'compatibility_score' => $this->calculateCompatibilityScore($value),
        ];

        $this->update([
            'ai_analysis' => array_merge($this->ai_analysis ?? [], [
                'ai_recommendation' => $recommendation,
                'ai_recommended_at' => now()->toISOString(),
            ]),
        ]);

        return $recommendation;
    }

    private function suggestOptimalValue()
    {
        // ZenithaLMS: Suggest optimal value based on setting type and context
        $type = $this->type;
        $category = $this->category;
        
        switch ($type) {
            case self::TYPE_COLOR:
                return $this->suggestOptimalColor($category);
            case self::TYPE_FONT:
                return $this->suggestOptimalFont($category);
            case self::TYPE_SIZE:
                return $this->suggestOptimalSize($category);
            case self::TYPE_SPACING:
                return $this->suggestOptimalSpacing($category);
            case self::TYPE_BORDER:
                return $this->suggestOptimalBorder($category);
            default:
                return $this->value;
        }
    }

    private function suggestOptimalColor($category)
    {
        // ZenithaLMS: Suggest optimal color based on category
        switch ($category) {
            case self::CATEGORY_COLORS:
                return '#3B82F6'; // Primary blue
            case self::CATEGORY_FONTS:
                return '#374151'; // Dark gray for text
            case self::CATEGORY_NAVIGATION:
                return '#FFFFFF'; // White for navigation
            case self::CATEGORY_BUTTONS:
                return '#10B981'; // Green for buttons
            case self::CATEGORY_CARDS:
                return '#FFFFFF'; // White for cards
            default:
                return '#6B7280'; // Neutral gray
        }
    }

    private function suggestOptimalFont($category)
    {
        // ZenithaLMS: Suggest optimal font based on category
        switch ($category) {
            case self::CATEGORY_FONTS:
                return 'Inter'; // Modern, readable font
            case self::CATEGORY_CONTENT:
                return 'Georgia'; // Classic, readable font
            case self::CATEGORY_LAYOUT:
                return 'Inter'; // Modern, clean font
            case self::CATEGORY_COMPONENTS:
                return 'Inter'; // Consistent component font
            case self::CATEGORY_FORMS:
                return 'Inter'; // Clean form font
            default:
                return 'Inter'; // Default modern font
        }
    }

    private function suggestOptimalSize($category)
    {
        // ZenithaLMS: Suggest optimal size based on category
        switch ($category) {
            case self::CATEGORY_FONTS:
                return '16'; // Standard font size
            case self::CATEGORY_CONTENT:
                return '16'; // Readable content size
            case self::CATEGORY_NAVIGATION:
                return '14'; // Slightly smaller for navigation
            case self::CATEGORY_BUTTONS:
                return '14'; // Standard button size
            case self::CATEGORY_CARDS:
                return '16'; // Card content size
            case self::CATEGORY_FORMS:
                return '16'; // Form input size
            default:
                return '16'; // Default size
        }
    }

    private function suggestOptimalSpacing($category)
    {
        // ZenithaLMS: Suggest optimal spacing based on category
        switch ($category) {
            case self::CATEGORY_LAYOUT:
                return '16'; // Standard spacing
            case self::CATEGORY_COMPONENTS:
                return '12'; // Tighter component spacing
            case self::CATEGORY_CONTENT:
                return '24'; // Larger content spacing
            case self::CATEGORY_FORMS:
                return '16'; // Form spacing
            default:
                return '16'; // Default spacing
        }
    }

    private function suggestOptimalBorder($category)
    {
        // ZenithaLMS: Suggest optimal border based on category
        switch ($category) {
            case self::CATEGORY_BUTTONS:
                return '1'; // Subtle button border
            case self::CATEGORY_CARDS:
                return '1'; // Subtle card border
            case self::CATEGORY_FORMS:
                return '1'; // Standard form border
            case self::CATEGORY_NAVIGATION:
                return '0'; // No border for navigation
            default:
                return '1'; // Default border
        }
    }

    private function analyzeUsageContext()
    {
        // ZenithaLMS: Analyze usage context of this setting
        $context = [
            'usage_frequency' => $this->calculateUsageFrequency(),
            'user_satisfaction' => $this->predictUserSatisfaction(),
            'customization_trend' => $this->analyzeCustomizationTrend(),
            'device_compatibility' => $this->analyzeDeviceCompatibility(),
        ];

        return $context;
    }

    private function calculateUsageFrequency()
    {
        // ZenithaLMS: Calculate how frequently this setting is used
        // This would be based on analytics data in a real implementation
        return 'medium'; // Placeholder
    }

    private function predictUserSatisfaction()
    {
        // ZenithLMS: Predict user satisfaction with current value
        $currentValue = $this->value;
        $optimalValue = $this->suggestOptimalValue();
        
        if ($currentValue === $optimalValue) {
            return 'high';
        } elseif ($this->isSimilarTo($optimalValue)) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function analyzeCustomizationTrend()
    {
        // ZenithaLMS: Analyze customization trend for this setting
        return 'stable'; // Placeholder
    }

    private function analyzeDeviceCompatibility()
    {
        // ZenithaLMS: Analyze device compatibility
        return 'high'; // Placeholder
    }

    private function predictUserPreference()
    {
        // ZenithLMS: Predict user preference for this setting
        $user = auth()->user();
        
        // Analyze user's theme preferences
        $userPreferences = $user->theme_preferences ?? [];
        
        // Check if user has similar settings
        $similarSettings = $this->findSimilarSettings($userPreferences);
        
        if ($similarSettings > 0) {
            return 'consistent';
        } elseif ($this->isPopularValue()) {
            return 'trending';
        } else {
            return 'neutral';
        }
    }

    private function isSimilarTo($value)
    {
        // ZenithaLMS: Check if value is similar to another setting
        $allSettings = ThemeSetting::where('type', $this->type)
            ->where('theme_id', $this->theme_id)
            ->where('key', '!=', $this->key)
            ->pluck('value');
        
        foreach ($allSettings as $setting) {
            if ($this->calculateSimilarity($value, $setting) > 0.8) {
                return true;
            }
        }
        
        return false;
    }

    private function isPopularValue()
    {
        // ZenithaLMS: Check if this is a popular value
        $popularColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        $popularFonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat'];
        $popularSizes = [12, 14, 16, 18, 20, 24];
        
        switch ($this->type) {
            case self::TYPE_COLOR:
                return in_array($this->value, $popularColors);
            case self::TYPE_FONT:
                return in_array($this->value, $popularFonts);
            case self::TYPE_SIZE:
                return in_array((int)$this->value, $popularSizes);
            default:
                return false;
        }
    }

    private function calculateSimilarity($value1, $value2)
    {
        // ZenithaLMS: Calculate similarity between two values
        if ($value1 === $value2) {
            return 1.0;
        }
        
        // Simple similarity calculation
        $common = similar_text($value1, $value2);
        $total = strlen($value1) + strlen($value2);
        
        return $total > 0 ? ($common / $total) : 0;
    }

    private function calculateAccessibilityScore($value)
    {
        // ZenithaLMS: Calculate accessibility score for this setting
        $score = 0.5; // Base score
        
        switch ($this->type) {
            case self::TYPE_COLOR:
                $score += $this->calculateColorAccessibility($value);
                break;
            case self::TYPE_FONT:
                $score += $this->calculateFontAccessibility($value);
                break;
            case self::TYPE_SIZE:
                $score += $this->calculateSizeAccessibility($value);
                break;
            case self::TYPE_SPACING:
                $score += $this->calculateSpacingAccessibility($value);
                break;
            case self::TYPE_BORDER:
                $score += $this->calculateBorderAccessibility($value);
                break;
        }
        
        return min(1.0, $score);
    }

    private function calculateColorAccessibility($color)
    {
        // ZenithaLMS: Calculate color accessibility
        $score = 0.5;
        
        // Check contrast ratio (simplified)
        $luminance = $this->calculateLuminance($color);
        
        if ($luminance > 0.7) {
            $score += 0.3; // Good contrast
        } elseif ($luminance < 0.3) {
            $score -= 0.3; // Poor contrast
        }
        
        return min(1.0, $score);
    }

    private function calculateLuminance($color)
    {
        // ZenithaLMS: Calculate relative luminance (simplified)
        $hex = ltrim($color, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    }

    private function calculateFontAccessibility($font)
    {
        // ZenithaLMS: Calculate font accessibility
        $score = 0.5;
        
        $safeFonts = ['Arial', 'Helvetica', 'Verdana', 'Georgia', 'Times New Roman'];
        
        if (in_array($font, $safeFonts)) {
            $score += 0.5;
        }
        
        return min(1.0, $score);
    }

    private function calculateSizeAccessibility($size)
    {
        // ZenithaLMS: Calculate size accessibility
        $size = (int)$size;
        
        if ($size >= 14 && $size <= 18) {
            return 1.0; // Good size range
        } elseif ($size >= 12 && $size <= 24) {
            return 0.8; // Acceptable size range
        } else {
            return 0.5; // May be too small or too large
        }
    }

    private function calculateSpacingAccessibility($spacing)
    {
        // ZenithaLMS: Calculate spacing accessibility
        $spacing = (int)$spacing;
        
        if ($spacing >= 12 && $spacing <= 24) {
            return 1.0; // Good spacing range
        } elseif ($spacing >= 8 && $spacing <= 32) {
            return 0.8; // Acceptable spacing range
        } else {
            return 0.5; // May be too small or too large
        }
    }

    private function calculateBorderAccessibility($border)
    {
        // ZenithaLMS: Calculate border accessibility
        $border = (int)$border;
        
        if ($border >= 1 && $border <= 2) {
            return 1.0; // Good border range
        } elseif ($border >= 0 && $border <= 4) {
            return 0.8; // Acceptable border range
        } else {
            return 0.5; // May be too thick or too thin
        }
    }

    private function calculatePerformanceImpact($value)
    {
        // ZenithaLMS: Calculate performance impact of this setting
        $impact = 0.5; // Base impact
        
        switch ($this->type) {
            case self::TYPE_ANIMATION:
                // Animations can impact performance
                $impact += 0.3;
                break;
            case self::TYPE_IMAGE:
                // Large images can impact performance
                $impact += 0.2;
                break;
            case self::TYPE_SHADOW:
                // Complex shadows can impact performance
                $impact += 0.1;
                break;
        }
        
        return min(1.0, $impact);
    }

    private function calculateAestheticScore($value)
    {
        // ZenithaLMS: Calculate aesthetic score for this setting
        $score = 0.5; // Base score
        
        switch ($this->type) {
            case self::TYPE_COLOR:
                $score += $this->calculateColorAesthetic($value);
                break;
            case self::TYPE_FONT:
                $score += $this->calculateFontAesthetic($value);
                break;
            case self::TYPE_SHADOW:
                $score += $this->calculateShadowAesthetic($value);
                break;
        }
        
        return min(1.0, $score);
    }

    private function calculateColorAesthetic($color)
    {
        // ZenithaLMS: Calculate color aesthetic score
        $score = 0.5;
        
        // Check if it's a harmonious color
        $hsl = $this->hexToHsl($color);
        
        if ($hsl['s'] >= 30 && $hsl['s'] <= 240) {
            $score += 0.3; // Good hue range
        }
        
        if ($hsl['l'] >= 30 && $hsl['l'] <= 70) {
            $score += 0.2; // Good lightness range
        }
        
        return min(1.0, $score);
    }

    private function calculateFontAesthetic($font)
    {
        // ZenithaLMS: Calculate font aesthetic score
        $score = 0.5;
        
        $modernFonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins'];
        
        if (in_array($font, $modernFonts)) {
            $score += 0.5;
        }
        
        return min(1.0, $score);
    }

    private function calculateShadowAesthetic($value)
    {
        // ZenithaLMS: Calculate shadow aesthetic score
        $score = 0.5;
        
        // Subtle shadows are generally more aesthetic
        if ($value === '0' || $value === 'none') {
            $score += 0.3;
        } elseif ($value === '1') {
            $score += 0.2;
        }
        
        return min(1.0, $score);
    }

    private function calculateCompatibilityScore($value)
    {
        // ZenithLMS: Calculate browser compatibility
        $score = 0.5; // Base score
        
        // Most modern browsers support basic CSS
        $score += 0.4;
        
        // Check for vendor prefixes
        if ($this->requiresVendorPrefix($value)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function requiresVendorPrefix($value)
    {
        // ZenithLMS: Check if value requires vendor prefix
        $properties = [
            'transform', 'transition', 'animation', 'box-shadow', 'text-shadow',
            'border-radius', 'background-image', 'filter',
        ];
        
        foreach ($properties as $property) {
            if (strpos($value, $property) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function hexToHsl($hex)
    {
        // ZenithaLMS: Convert hex to HSL
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        
        $h = $max === $min ? 0 : ($max === $r ? ($g - $b) / ($max - $min) : ($r - $g) / ($max - $min)) * 360;
        $s = ($max / 255) * 100;
        $l = ($max + $min) / 510 * 100;
        
        return [
            'h' => round($h),
            's' => round($s),
            'l' => round($l),
        ];
    }

    /**
     * ZenithaLMS: Helper Methods
     */
    public static function getTypes()
    {
        return [
            self::TYPE_COLOR => 'Color',
            self::TYPE_FONT => 'Font',
            self::TYPE_SIZE => 'Size',
            self::TYPE_SPACING => 'Spacing',
            self::TYPE_BORDER => 'Border',
            self::TYPE_SHADOW => 'Shadow',
            self::TYPE_ANIMATION => 'Animation',
            self::TYPE_IMAGE => 'Image',
            self::TYPE_BOOLEAN => 'Boolean',
            self::TYPE_TEXT => 'Text',
            self::TYPE_NUMBER => 'Number',
        ];
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_COLORS => 'Colors',
            self::CATEGORY_FONTS => 'Fonts',
            self::CATEGORY_LAYOUT => 'Layout',
            self::CATEGORY_COMPONENTS => 'Components',
            self::CATEGORY_NAVIGATION => 'Navigation',
            self::CATEGORY_CONTENT => 'Content',
            self::CATEGORY_FORMS => 'Forms',
            self::CATEGORY_BUTTONS => 'Buttons',
            self::CATEGORY_CARDS => 'Cards',
        ];
    }
}
