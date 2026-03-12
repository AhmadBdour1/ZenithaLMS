<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            // Web Development Skills
            [
                'name' => 'HTML5',
                'slug' => 'html5',
                'description' => 'Master semantic HTML5 markup, forms, multimedia, and modern web standards.',
                'category' => 'Web Development',
                'level' => 'beginner',
                'prerequisites' => json_encode([]),
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'CSS3',
                'slug' => 'css3',
                'description' => 'Advanced CSS3 styling, animations, flexbox, grid, and responsive design.',
                'category' => 'Web Development',
                'level' => 'beginner',
                'prerequisites' => json_encode(['html5']),
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'JavaScript ES6+',
                'slug' => 'javascript-es6',
                'description' => 'Modern JavaScript programming including ES6+, async programming, and DOM manipulation.',
                'category' => 'Web Development',
                'level' => 'intermediate',
                'prerequisites' => json_encode(['html5', 'css3']),
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'React.js',
                'slug' => 'reactjs',
                'description' => 'Build interactive user interfaces with React, hooks, state management, and component architecture.',
                'category' => 'Web Development',
                'level' => 'intermediate',
                'prerequisites' => json_encode(['javascript-es6']),
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Node.js',
                'slug' => 'nodejs',
                'description' => 'Server-side JavaScript runtime for building scalable network applications and APIs.',
                'category' => 'Web Development',
                'level' => 'intermediate',
                'prerequisites' => json_encode(['javascript-es6']),
                'sort_order' => 5,
                'is_active' => true,
            ],
            
            // Mobile Development Skills
            [
                'name' => 'Swift Programming',
                'slug' => 'swift-programming',
                'description' => 'Apple\'s modern programming language for iOS, macOS, and other Apple platforms.',
                'category' => 'Mobile Development',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Kotlin Programming',
                'slug' => 'kotlin-programming',
                'description' => 'Modern programming language for Android development and cross-platform applications.',
                'category' => 'Mobile Development',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'React Native',
                'slug' => 'react-native',
                'description' => 'Build native mobile apps using React and JavaScript for iOS and Android.',
                'category' => 'Mobile Development',
                'level' => 'advanced',
                'prerequisites' => json_encode(['reactjs', 'javascript-es6']),
                'sort_order' => 8,
                'is_active' => true,
            ],
            
            // AI & Machine Learning Skills
            [
                'name' => 'Python Programming',
                'slug' => 'python-programming',
                'description' => 'Versatile programming language essential for data science, AI, and web development.',
                'category' => 'Artificial Intelligence',
                'level' => 'beginner',
                'prerequisites' => json_encode([]),
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Machine Learning Fundamentals',
                'slug' => 'machine-learning-fundamentals',
                'description' => 'Core concepts of machine learning, supervised and unsupervised learning, and model evaluation.',
                'category' => 'Artificial Intelligence',
                'level' => 'intermediate',
                'prerequisites' => json_encode(['python-programming']),
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Deep Learning with TensorFlow',
                'slug' => 'deep-learning-tensorflow',
                'description' => 'Build neural networks and deep learning models using TensorFlow and Keras.',
                'category' => 'Artificial Intelligence',
                'level' => 'advanced',
                'prerequisites' => json_encode(['python-programming', 'machine-learning-fundamentals']),
                'sort_order' => 11,
                'is_active' => true,
            ],
            
            // Business Skills
            [
                'name' => 'Strategic Planning',
                'slug' => 'strategic-planning',
                'description' => 'Develop and implement business strategies, market analysis, and competitive positioning.',
                'category' => 'Business Management',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Financial Analysis',
                'slug' => 'financial-analysis',
                'description' => 'Analyze financial statements, investment opportunities, and business performance metrics.',
                'category' => 'Financial Management',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 13,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Marketing Strategy',
                'slug' => 'digital-marketing-strategy',
                'description' => 'Plan and execute comprehensive digital marketing campaigns across multiple channels.',
                'category' => 'Digital Marketing',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 14,
                'is_active' => true,
            ],
            
            // Creative Skills
            [
                'name' => 'Adobe Photoshop',
                'slug' => 'adobe-photoshop',
                'description' => 'Professional image editing, photo manipulation, and digital art creation.',
                'category' => 'Graphic Design',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'UI/UX Design Principles',
                'slug' => 'ui-ux-design-principles',
                'description' => 'User interface and user experience design, prototyping, and usability testing.',
                'category' => 'Graphic Design',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 16,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Illustration',
                'slug' => 'digital-illustration',
                'description' => 'Create digital artwork, illustrations, and concept art using professional tools.',
                'category' => 'Digital Art',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 17,
                'is_active' => true,
            ],
            [
                'name' => 'Portrait Photography',
                'slug' => 'portrait-photography',
                'description' => 'Professional portrait photography techniques, lighting, and post-processing.',
                'category' => 'Photography',
                'level' => 'intermediate',
                'prerequisites' => json_encode([]),
                'sort_order' => 18,
                'is_active' => true,
            ],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
