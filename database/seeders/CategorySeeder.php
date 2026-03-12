<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, create parent categories
        $parentCategories = [
            // Technology Categories
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Learn modern web development with HTML, CSS, JavaScript, and popular frameworks like React, Vue, and Angular.',
                'icon' => 'fas fa-code',
                'color' => '#4F46E5',
                'parent_id' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Development',
                'slug' => 'mobile-development',
                'description' => 'Create native and cross-platform mobile applications for iOS and Android using Swift, Kotlin, React Native, and Flutter.',
                'icon' => 'fas fa-mobile-alt',
                'color' => '#10B981',
                'parent_id' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Artificial Intelligence',
                'slug' => 'artificial-intelligence',
                'description' => 'Explore machine learning, deep learning, natural language processing, and AI applications with Python and TensorFlow.',
                'icon' => 'fas fa-brain',
                'color' => '#8B5CF6',
                'parent_id' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Cybersecurity',
                'slug' => 'cybersecurity',
                'description' => 'Master network security, ethical hacking, cryptography, and protect systems from cyber threats.',
                'icon' => 'fas fa-shield-alt',
                'color' => '#EF4444',
                'parent_id' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
            
            // Business Categories
            [
                'name' => 'Business Management',
                'slug' => 'business-management',
                'description' => 'Develop leadership skills, strategic thinking, and business acumen for modern management roles.',
                'icon' => 'fas fa-briefcase',
                'color' => '#06B6D4',
                'parent_id' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'description' => 'Learn SEO, social media marketing, content marketing, and digital advertising strategies.',
                'icon' => 'fas fa-bullhorn',
                'color' => '#F59E0B',
                'parent_id' => null,
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Financial Management',
                'slug' => 'financial-management',
                'description' => 'Master financial analysis, investment strategies, accounting, and corporate finance principles.',
                'icon' => 'fas fa-chart-line',
                'color' => '#10B981',
                'parent_id' => null,
                'sort_order' => 7,
                'is_active' => true,
            ],
            
            // Creative Arts Categories
            [
                'name' => 'Graphic Design',
                'slug' => 'graphic-design',
                'description' => 'Create stunning visual designs using Adobe Creative Suite, Figma, and modern design principles.',
                'icon' => 'fas fa-palette',
                'color' => '#EC4899',
                'parent_id' => null,
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Art',
                'slug' => 'digital-art',
                'description' => 'Master digital painting, illustration, 3D modeling, and concept art using professional tools.',
                'icon' => 'fas fa-paint-brush',
                'color' => '#8B5CF6',
                'parent_id' => null,
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Photography',
                'slug' => 'photography',
                'description' => 'Learn professional photography techniques, photo editing, and composition for stunning images.',
                'icon' => 'fas fa-camera',
                'color' => '#6366F1',
                'parent_id' => null,
                'sort_order' => 10,
                'is_active' => true,
            ],
        ];

        // Create parent categories first
        $createdCategories = [];
        foreach ($parentCategories as $category) {
            $createdCategory = Category::create($category);
            $createdCategories[$createdCategory->slug] = $createdCategory;
        }

        // Now create subcategories with correct parent_id references
        $subcategories = [
            [
                'name' => 'Frontend Development',
                'slug' => 'frontend-development',
                'description' => 'Master HTML, CSS, JavaScript, and modern frontend frameworks.',
                'icon' => 'fas fa-laptop-code',
                'color' => '#4F46E5',
                'parent_id' => $createdCategories['web-development']->id,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Backend Development',
                'slug' => 'backend-development',
                'description' => 'Learn server-side programming with Node.js, Python, PHP, and databases.',
                'icon' => 'fas fa-server',
                'color' => '#4F46E5',
                'parent_id' => $createdCategories['web-development']->id,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'iOS Development',
                'slug' => 'ios-development',
                'description' => 'Build native iOS apps using Swift and Xcode.',
                'icon' => 'fab fa-apple',
                'color' => '#10B981',
                'parent_id' => $createdCategories['mobile-development']->id,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Android Development',
                'slug' => 'android-development',
                'description' => 'Create Android apps using Kotlin and Android Studio.',
                'icon' => 'fab fa-android',
                'color' => '#10B981',
                'parent_id' => $createdCategories['mobile-development']->id,
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        // Create subcategories
        foreach ($subcategories as $subcategory) {
            Category::create($subcategory);
        }
    }
}
