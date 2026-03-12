<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'preview_image',
        'template_data',
        'css_styles',
        'html_template',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'template_data' => 'array',
        'css_styles' => 'json',
        'html_template' => 'json',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getPreviewImageUrlAttribute()
    {
        return $this->preview_image ? asset('storage/' . $this->preview_image) : null;
    }

    public function isDefault()
    {
        return $this->is_default;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function getTemplateData()
    {
        return $this->template_data ?? [];
    }

    public function getCssStyles()
    {
        return $this->css_styles ?? [];
    }

    public function getHtmlTemplate()
    {
        return $this->html_template ?? [];
    }

    /**
     * ZenithaLMS: Template Methods
     */
    public function generateCertificate($certificate)
    {
        // ZenithaLMS: Generate certificate using this template
        $templateData = $this->getTemplateData();
        $cssStyles = $this->getCssStyles();
        $htmlTemplate = $this->getHtmlTemplate();

        // Merge certificate data with template
        $mergedData = array_merge($templateData, [
            'certificate_number' => $certificate->certificate_number,
            'student_name' => $certificate->user->name,
            'course_name' => $certificate->course->title,
            'title' => $certificate->title,
            'description' => $certificate->description,
            'issued_at' => $certificate->issued_at->format('F j, Y'),
            'completion_date' => $certificate->completed_at ? $certificate->completed_at->format('F j, Y') : null,
            'verification_code' => $certificate->verification_code,
            'instructor_name' => $certificate->course->instructor->name ?? 'N/A',
            'organization_name' => $certificate->course->organization->name ?? 'ZenithaLMS',
        ]);

        // Generate HTML
        $html = $this->generateHtml($mergedData, $htmlTemplate);

        // Apply CSS styles
        $styledHtml = $this->applyCssStyles($html, $cssStyles);

        return $styledHtml;
    }

    private function generateHtml($data, $template)
    {
        // ZenithaLMS: Generate HTML from template and data
        $html = $template['html'] ?? $this->getDefaultHtmlTemplate();
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $html = str_replace('{' . $key . '}', $value, $html);
        }

        return $html;
    }

    private function applyCssStyles($html, $css)
    {
        // ZenithaLMS: Apply CSS styles to HTML
        $styles = $css['styles'] ?? '';
        
        if (!empty($styles)) {
            $html = '<style>' . $styles . '</style>' . $html;
        }

        return $html;
    }

    private function getDefaultHtmlTemplate()
    {
        // ZenithaLMS: Default HTML template
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>{title}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .certificate {
                        background: white;
                        border-radius: 20px;
                        padding: 40px;
                        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                        text-align: center;
                        max-width: 800px;
                        position: relative;
                        overflow: hidden;
                    }
                    .certificate::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 10px;
                        background: linear-gradient(90deg, #667eea, #764ba2);
                    }
                    .certificate-header {
                        margin-bottom: 30px;
                    }
                    .certificate-title {
                        font-size: 36px;
                        font-weight: bold;
                        color: #333;
                        margin-bottom: 10px;
                    }
                    .certificate-subtitle {
                        font-size: 18px;
                        color: #666;
                        margin-bottom: 20px;
                    }
                    .certificate-body {
                        margin-bottom: 30px;
                    }
                    .student-info {
                        font-size: 20px;
                        color: #333;
                        margin-bottom: 15px;
                    }
                    .course-info {
                        font-size: 18px;
                        color: #666;
                        margin-bottom: 15px;
                    }
                    .certificate-footer {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 2px solid #eee;
                    }
                    .certificate-details {
                        display: flex;
                        justify-content: space-between;
                        font-size: 14px;
                        color: #666;
                    }
                    .verification-code {
                        font-family: monospace;
                        background: #f5f5f5;
                        padding: 5px 10px;
                        border-radius: 5px;
                        margin-top: 10px;
                    }
                    .signature {
                        margin-top: 30px;
                        text-align: right;
                        font-style: italic;
                        color: #666;
                    }
                </style>
            </head>
            <body>
                <div class="certificate">
                    <div class="certificate-header">
                        <div class="certificate-title">Certificate of Completion</div>
                        <div class="certificate-subtitle">This is to certify that</div>
                    </div>
                    <div class="certificate-body">
                        <div class="student-info">
                            <strong>{student_name}</strong>
                        </div>
                        <div class="course-info">
                            has successfully completed the course
                        </div>
                        <div class="course-info">
                            <strong>{course_name}</strong>
                        </div>
                        <div class="course-info">
                            with a grade of <strong>A+</strong>
                        </div>
                    </div>
                    <div class="certificate-footer">
                        <div class="certificate-details">
                            <div>
                                <strong>Date:</strong> {issued_at}
                            </div>
                            <div>
                                <strong>Certificate #:</strong> {certificate_number}
                            </div>
                        </div>
                        <div class="verification-code">
                            <strong>Verification Code:</strong> {verification_code}
                        </div>
                        <div class="signature">
                            <div>{instructor_name}</div>
                            <div>{organization_name}</div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ';
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiOptimization()
    {
        // ZenithaLMS: AI-powered template optimization
        $optimization = [
            'design_score' => $this->calculateDesignScore(),
            'readability_score' => $this->calculateReadabilityScore(),
            'professionalism_score' => $this->calculateProfessionalismScore(),
            'color_harmony' => $this->analyzeColorHarmony(),
            'typography_score' => $this->analyzeTypography(),
            'layout_score' => $this->analyzeLayout(),
            'suggestions' => $this->generateOptimizationSuggestions(),
        ];

        $this->update([
            'template_data' => array_merge($this->template_data ?? [], [
                'ai_optimization' => $optimization,
                'ai_optimized_at' => now()->toISOString(),
            ]),
        ]);

        return $optimization;
    }

    private function calculateDesignScore()
    {
        // ZenithaLMS: Calculate design score
        $score = 0;
        $templateData = $this->getTemplateData();
        
        // Check for essential elements
        if (isset($templateData['has_header']) && $templateData['has_header']) {
            $score += 20;
        }
        
        if (isset($templateData['has_footer']) && $templateData['has_footer']) {
            $score += 20;
        }
        
        if (isset($templateData['has_logo']) && $templateData['has_logo']) {
            $score += 15;
        }
        
        if (isset($templateData['has_signature']) && $templateData['has_signature']) {
            $score += 15;
        }
        
        if (isset($templateData['has_watermark']) && $templateData['has_watermark']) {
            $score += 10;
        }
        
        if (isset($templateData['has_border']) && $templateData['has_border']) {
            $score += 10;
        }
        
        if (isset($templateData['has_background']) && $templateData['has_background']) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    private function calculateReadabilityScore()
    {
        // ZenithaLMS: Calculate readability score
        $score = 0;
        $cssStyles = $this->getCssStyles();
        
        // Check font size
        if (isset($cssStyles['font_size'])) {
            $fontSize = $cssStyles['font_size'];
            if ($fontSize >= 14 && $fontSize <= 18) {
                $score += 25;
            }
        }
        
        // Check line height
        if (isset($cssStyles['line_height'])) {
            $lineHeight = $cssStyles['line_height'];
            if ($lineHeight >= 1.4 && $lineHeight <= 1.6) {
                $score += 25;
            }
        }
        
        // Check contrast
        if (isset($cssStyles['text_color']) && isset($cssStyles['background_color'])) {
            $contrast = $this->calculateColorContrast($cssStyles['text_color'], $cssStyles['background_color']);
            if ($contrast >= 4.5) {
                $score += 25;
            }
        }
        
        // Check spacing
        if (isset($cssStyles['padding']) && $cssStyles['padding'] >= 20) {
            $score += 15;
        }
        
        if (isset($cssStyles['margin']) && $cssStyles['margin'] >= 20) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    private function calculateProfessionalismScore()
    {
        // ZenithaLMS: Calculate professionalism score
        $score = 0;
        $templateData = $this->getTemplateData();
        
        // Check for professional elements
        if (isset($templateData['has_official_seal']) && $templateData['has_official_seal']) {
            $score += 20;
        }
        
        if (isset($template_data['has_official_signature']) && $templateData['has_official_signature']) {
            $score += 20;
        }
        
        if (isset($templateData['has_serial_number']) && $templateData['has_serial_number']) {
            $score += 15;
        }
        
        if (isset($templateData['has_issue_date']) && $templateData['has_issue_date']) {
            $score += 15;
        }
        
        if (isset($templateData['has_expiration_date']) && $templateData['has_expiration_date']) {
            $score += 10;
        }
        
        if (isset($templateData['has_verification_code']) && $templateData['has_verification_code']) {
            $score += 10;
        }
        
        if (isset($templateData['has_barcode']) && $templateData['has_barcode']) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    private function analyzeColorHarmony()
    {
        // ZenithaLMS: Analyze color harmony
        $cssStyles = $this->getCssStyles();
        
        if (!isset($cssStyles['primary_color']) || !isset($cssStyles['secondary_color'])) {
            return ['score' => 50, 'analysis' => 'No color scheme defined'];
        }
        
        $primaryColor = $cssStyles['primary_color'];
        $secondaryColor = $cssStyles['secondary_color'];
        
        // Simple color harmony check
        $harmonyScore = 70; // Base score
        
        // Check if colors are complementary
        if ($this->areColorsComplementary($primaryColor, $secondaryColor)) {
            $harmonyScore = 90;
        } elseif ($this->areColorsAnalogous($primaryColor, $secondaryColor)) {
            $harmonyScore = 85;
        } elseif ($this->areColorsTriadic($primaryColor, $secondaryColor)) {
            $harmonyScore = 80;
        }
        
        return [
            'score' => $harmonyScore,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'harmony_type' => $this->getColorHarmonyType($primaryColor, $secondaryColor),
        ];
    }

    private function analyzeTypography()
    {
        // ZenithaLMS: Analyze typography
        $cssStyles = $this->getCssStyles();
        
        $score = 0;
        $analysis = [];
        
        // Check font family
        if (isset($cssStyles['font_family'])) {
            $fontFamily = $cssStyles['font_family'];
            if (in_array($fontFamily, ['Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Verdana'])) {
                $score += 30;
                $analysis['font_family'] = 'Professional';
            } else {
                $analysis['font_family'] = 'Custom';
            }
        }
        
        // Check font sizes
        if (isset($cssStyles['font_sizes'])) {
            $fontSizes = $cssStyles['font_sizes'];
            if (count($fontSizes) >= 3) {
                $score += 30;
                $analysis['font_sizes'] = 'Good variety';
            } else {
                $analysis['font_sizes'] = 'Limited variety';
            }
        }
        
        // Check font weights
        if (isset($cssStyles['font_weights'])) {
            $fontWeights = $cssStyles['font_weights'];
            if (in_array('bold', $fontWeights) && in_array('normal', $fontWeights)) {
                $score += 20;
                $analysis['font_weights'] = 'Good variety';
            }
        }
        
        // Check text hierarchy
        if (isset($cssStyles['has_heading_hierarchy']) && $cssStyles['has_heading_hierarchy']) {
            $score += 20;
            $analysis['hierarchy'] = 'Clear hierarchy';
        }
        
        return [
            'score' => $score,
            'analysis' => $analysis,
        ];
    }

    private function analyzeLayout()
    {
        // ZenithaLMS: Analyze layout
        $templateData = $this->getTemplateData();
        
        $score = 0;
        $analysis = [];
        
        // Check layout structure
        if (isset($templateData['layout_type'])) {
            $layoutType = $templateData['layout_type'];
            if (in_array($layoutType, ['centered', 'balanced', 'professional'])) {
                $score += 30;
                $analysis['layout_type'] = 'Professional';
            } else {
                $analysis['layout_type'] = $layoutType;
            }
        }
        
        // Check spacing
        if (isset($templateData['has_consistent_spacing']) && $templateData['has_consistent_spacing']) {
            $score += 25;
            $analysis['spacing'] = 'Consistent';
        }
        
        // Check alignment
        if (isset($templateData['has_proper_alignment']) && $templateData['has_proper_alignment']) {
            $score += 25;
            $analysis['alignment'] = 'Proper';
        }
        
        // Check visual hierarchy
        if (isset($templateData['has_visual_hierarchy']) && $templateData['has_visual_hierarchy']) {
            $score += 20;
            $analysis['hierarchy'] = 'Clear';
        }
        
        return [
            'score' => $score,
            'analysis' => $analysis,
        ];
    }

    private function generateOptimizationSuggestions()
    {
        // ZenithaLMS: Generate optimization suggestions
        $suggestions = [];
        
        $designScore = $this->calculateDesignScore();
        $readabilityScore = $this->calculateReadabilityScore();
        $professionalismScore = $this->calculateProfessionalismScore();
        
        if ($designScore < 70) {
            $suggestions[] = 'Add more design elements like borders, backgrounds, or watermarks';
            $suggestions[] = 'Include a logo or official seal for better branding';
        }
        
        if ($readabilityScore < 70) {
            $suggestions[] = 'Improve font size and line height for better readability';
            $suggestions[] = 'Ensure text color has sufficient contrast with background';
            $suggestions[] = 'Add more padding and margins for better spacing';
        }
        
        if ($professionalismScore < 70) {
            $suggestions[] = 'Add official elements like serial numbers or verification codes';
            $suggestions[] = 'Include issue and expiration dates';
            $suggestions[] = 'Add official signature or seal';
        }
        
        $colorHarmony = $this->analyzeColorHarmony();
        if ($colorHarmony['score'] < 80) {
            $suggestions[] = 'Improve color harmony for better visual appeal';
            $suggestions[] = 'Use complementary or analogous color schemes';
        }
        
        return $suggestions;
    }

    private function calculateColorContrast($color1, $color2)
    {
        // ZenithaLMS: Calculate color contrast ratio (simplified)
        $luminance1 = $this->getLuminance($color1);
        $luminance2 = $this->getLuminance($color2);
        
        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function getLuminance($color)
    {
        // ZenithaLMS: Calculate luminance (simplified)
        $hex = ltrim($color, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    private function areColorsComplementary($color1, $color2)
    {
        // ZenithaLMS: Check if colors are complementary
        $hsl1 = $this->hexToHsl($color1);
        $hsl2 = $this->hexToHsl($color2);
        
        $hueDiff = abs($hsl1['h'] - $hsl2['h']);
        
        return ($hueDiff >= 165 && $hueDiff <= 195);
    }

    private function areColorsAnalogous($color1, $color2)
    {
        // ZenithaLMS: Check if colors are analogous
        $hsl1 = $this->hexToHsl($color1);
        $hsl2 = $this->hexToHsl($color2);
        
        $hueDiff = abs($hsl1['h'] - $hsl2['h']);
        
        return ($hueDiff >= 30 && $hueDiff <= 60);
    }

    private function areColorsTriadic($color1, $color2)
    {
        // ZenithaLMS: Check if colors are triadic
        $hsl1 = $this->hexToHsl($color1);
        $hsl2 = $this->hexToHsl($color2);
        
        $hueDiff = abs($hsl1['h'] - $hsl2['h']);
        
        return ($hueDiff >= 120 && $hueDiff <= 240);
    }

    private function getColorHarmonyType($color1, $color2)
    {
        if ($this->areColorsComplementary($color1, $color2)) {
            return 'complementary';
        } elseif ($this->areColorsAnalogous($color1, $color2)) {
            return 'analogous';
        } elseif ($this->areColorsTriadic($color1, $color2)) {
            return 'triadic';
        }
        
        return 'monochromatic';
    }

    private function hexToHsl($hex)
    {
        // ZenithaLMS: Convert hex to HSL (simplified)
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        
        $h = 0;
        $s = 0;
        $l = ($max + $min) / 2;
        
        if ($delta !== 0) {
            if ($max === $r) {
                $h = (($g - $b) / $delta) % 6;
            } elseif ($max === $g) {
                $h = (($b - $r) / $delta) + 2;
            } else {
                $h = (($r - $g) / $delta) + 4;
            }
            
            $s = $delta / (1 - abs(2 * $l - 1));
        }
        
        return [
            'h' => round($h * 60),
            's' => round($s * 100),
            'l' => round($l * 100),
        ];
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getDefaultTemplate()
    {
        return self::where('is_default', true)->first();
    }

    public static function getActiveTemplates()
    {
        return self::active()->ordered()->get();
    }
}
