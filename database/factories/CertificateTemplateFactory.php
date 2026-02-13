<?php

namespace Database\Factories;

use App\Models\CertificateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition()
    {
        $name = $this->faker->randomElement([
            'Professional Certificate',
            'Modern Certificate',
            'Classic Certificate',
            'Minimalist Certificate',
            'Elegant Certificate',
            'Corporate Certificate',
            'Academic Certificate',
            'Creative Certificate',
        ]);
        
        return [
            'name' => $name,
            'slug' => $this->faker->slug(),
            'description' => $this->faker->sentence(),
            'preview_image' => 'certificates/' . $this->faker->word() . '.jpg',
            'template_data' => [
                'has_header' => true,
                'has_footer' => true,
                'has_logo' => true,
                'has_signature' => true,
                'has_watermark' => $this->faker->boolean(50),
                'has_border' => true,
                'has_background' => true,
                'layout_type' => $this->faker->randomElement(['centered', 'balanced', 'professional']),
                'has_consistent_spacing' => true,
                'has_proper_alignment' => true,
                'has_visual_hierarchy' => true,
                'has_official_seal' => $this->faker->boolean(70),
                'has_official_signature' => true,
                'has_serial_number' => true,
                'has_issue_date' => true,
                'has_expiration_date' => $this->faker->boolean(30),
                'has_verification_code' => true,
                'has_barcode' => $this->faker->boolean(40),
            ],
            'css_styles' => [
                'font_family' => $this->faker->randomElement(['Arial', 'Georgia', 'Times New Roman', 'Verdana']),
                'font_size' => $this->faker->numberBetween(12, 18),
                'line_height' => $this->faker->randomFloat(1, 1.4, 1.6),
                'text_color' => '#333333',
                'background_color' => '#ffffff',
                'primary_color' => $this->faker->hexColor(),
                'secondary_color' => $this->faker->hexColor(),
                'padding' => $this->faker->numberBetween(20, 40),
                'margin' => $this->faker->numberBetween(20, 40),
                'font_sizes' => ['12px', '14px', '16px', '18px', '24px', '36px'],
                'font_weights' => ['normal', 'bold'],
                'has_heading_hierarchy' => true,
            ],
            'html_template' => [
                'html' => $this->faker->randomHtml(2, 4),
                'sections' => [
                    'header' => true,
                    'title' => true,
                    'student_info' => true,
                    'course_info' => true,
                    'completion_info' => true,
                    'footer' => true,
                ],
            ],
            'is_default' => $this->faker->boolean(20),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
    
    /**
     * Create a default template
     */
    public function default()
    {
        return $this->state([
            'is_default' => true,
            'name' => 'Default Certificate Template',
            'sort_order' => 0,
        ]);
    }
    
    /**
     * Create an inactive template
     */
    public function inactive()
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
    
    /**
     * Create a professional template
     */
    public function professional()
    {
        return $this->state([
            'name' => 'Professional Certificate',
            'template_data' => array_merge($this->faker->randomElements([
                'has_official_seal' => true,
                'has_official_signature' => true,
                'has_serial_number' => true,
                'has_verification_code' => true,
                'has_barcode' => true,
            ]), [
                'layout_type' => 'professional',
                'has_consistent_spacing' => true,
                'has_proper_alignment' => true,
                'has_visual_hierarchy' => true,
            ]),
            'css_styles' => [
                'font_family' => 'Times New Roman',
                'primary_color' => '#1a1a1a',
                'secondary_color' => '#333333',
            ],
        ]);
    }
    
    /**
     * Create a modern template
     */
    public function modern()
    {
        return $this->state([
            'name' => 'Modern Certificate',
            'template_data' => [
                'layout_type' => 'centered',
                'has_background' => true,
                'has_border' => false,
                'has_watermark' => true,
            ],
            'css_styles' => [
                'font_family' => 'Arial',
                'primary_color' => '#2563eb',
                'secondary_color' => '#64748b',
            ],
        ]);
    }
}
