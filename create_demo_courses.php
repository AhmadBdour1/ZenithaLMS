<?php

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

echo "Creating demo courses...\n";

// Get first category and instructor
$category = Category::first();
$instructor = User::where('role_id', 2)->first(); // Instructor role

if (!$category) {
    echo "No categories found. Creating demo category...\n";
    $category = Category::create([
        'name' => 'Web Development',
        'slug' => 'web-development',
        'description' => 'Learn web development from scratch',
        'is_active' => true,
    ]);
}

if (!$instructor) {
    echo "No instructors found. Creating demo instructor...\n";
    $instructor = User::first();
}

// Create demo courses
$courses = [
    [
        'title' => 'Complete Web Development Bootcamp',
        'description' => 'Learn HTML, CSS, JavaScript, React, Node.js and more in this comprehensive bootcamp.',
        'level' => 'beginner',
        'duration_minutes' => 1200,
        'price' => 89.99,
        'is_free' => false,
        'is_featured' => true,
        'is_published' => true,
        'what_you_will_learn' => json_encode([
            'HTML5 and semantic markup',
            'CSS3 and modern styling',
            'JavaScript ES6+',
            'React.js fundamentals',
            'Node.js and Express',
            'MongoDB database'
        ]),
        'requirements' => json_encode([
            'Basic computer skills',
            'No programming experience required'
        ]),
        'target_audience' => json_encode([
            'Beginners who want to learn web development',
            'Students looking to start a career in tech'
        ])
    ],
    [
        'title' => 'Advanced React and Redux',
        'description' => 'Master React.js with Redux, hooks, and advanced patterns.',
        'level' => 'advanced',
        'duration_minutes' => 800,
        'price' => 129.99,
        'is_free' => false,
        'is_featured' => false,
        'is_published' => true,
        'what_you_will_learn' => json_encode([
            'Advanced React patterns',
            'Redux state management',
            'React hooks',
            'Performance optimization',
            'Testing React applications'
        ]),
        'requirements' => json_encode([
            'JavaScript knowledge required',
            'Basic React experience recommended'
        ]),
        'target_audience' => json_encode([
            'Developers who know React basics',
            'Frontend developers looking to advance'
        ])
    ],
    [
        'title' => 'Introduction to Python Programming',
        'description' => 'Learn Python from scratch with hands-on projects and exercises.',
        'level' => 'beginner',
        'duration_minutes' => 600,
        'price' => 0,
        'is_free' => true,
        'is_featured' => false,
        'is_published' => true,
        'what_you_will_learn' => json_encode([
            'Python syntax and basics',
            'Data structures',
            'Object-oriented programming',
            'File handling',
            'Error handling'
        ]),
        'requirements' => json_encode([
            'Basic computer skills',
            'No programming experience required'
        ]),
        'target_audience' => json_encode([
            'Complete beginners to programming',
            'Students and professionals'
        ])
    ]
];

foreach ($courses as $courseData) {
    $courseData['slug'] = Str::slug($courseData['title']);
    $courseData['category_id'] = $category->id;
    $courseData['instructor_id'] = $instructor->id;
    $courseData['language'] = 'en';
    
    $course = Course::create($courseData);
    echo "Created course: " . $course->title . "\n";
}

echo "Demo courses created successfully!\n";
