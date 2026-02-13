<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            // Tech Academy Courses
            [
                'organization_id' => 1, // Tech Academy
                'instructor_id' => 4, // Dr. James Wilson
                'category_id' => 1, // Web Development
                'title' => 'Complete Web Development Bootcamp 2024',
                'slug' => 'complete-web-development-bootcamp-2024',
                'description' => 'Master modern web development from scratch. Learn HTML5, CSS3, JavaScript ES6+, React, Node.js, MongoDB, and more. Build real-world projects and launch your career as a full-stack developer.',
                'content' => 'This comprehensive web development course covers everything from basic HTML and CSS to advanced React and Node.js concepts. You\'ll build multiple projects including a social media platform, e-commerce site, and real-time chat application.',
                'thumbnail' => 'images/demo/courses/web-dev-bootcamp.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 89.99,
                'is_free' => false,
                'level' => 'beginner',
                'language' => 'en',
                'duration_minutes' => 2400, // 40 hours
                'requirements' => json_encode([
                    'No programming experience needed',
                    'Computer with internet access',
                    'Willingness to learn and practice'
                ]),
                'what_you_will_learn' => json_encode([
                    'Build 25+ web applications and websites',
                    'Master HTML5, CSS3, and modern JavaScript',
                    'Become proficient in React and Node.js',
                    'Understand backend development with Express and MongoDB',
                    'Deploy applications to production servers',
                    'Build responsive and mobile-friendly websites'
                ]),
                'target_audience' => json_encode([
                    'Complete beginners to programming',
                    'Students wanting to learn web development',
                    'Career changers looking to enter tech',
                    'Entrepreneurs who want to build their own websites'
                ]),
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            [
                'organization_id' => 1, // Tech Academy
                'instructor_id' => 5, // Lisa Thompson
                'category_id' => 11, // Frontend Development
                'title' => 'Advanced React.js: From Beginner to Expert',
                'slug' => 'advanced-reactjs-beginner-to-expert',
                'description' => 'Master React.js with hooks, context API, Redux, Next.js, and advanced patterns. Build production-ready applications with modern React development practices.',
                'content' => 'Deep dive into React ecosystem including advanced hooks, performance optimization, testing, and deployment strategies. Learn to build scalable React applications used in production.',
                'thumbnail' => 'images/demo/courses/advanced-react.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 79.99,
                'is_free' => false,
                'level' => 'intermediate',
                'language' => 'en',
                'duration_minutes' => 1800, // 30 hours
                'requirements' => json_encode([
                    'Basic JavaScript knowledge',
                    'Understanding of HTML and CSS',
                    'Some React experience helpful but not required'
                ]),
                'what_you_will_learn' => json_encode([
                    'Master React Hooks and custom hooks',
                    'Build scalable applications with Context API',
                    'Implement Redux for state management',
                    'Create server-side rendered apps with Next.js',
                    'Optimize React application performance',
                    'Test React applications with Jest and React Testing Library'
                ]),
                'target_audience' => json_encode([
                    'JavaScript developers wanting to learn React',
                    'React developers looking to advance their skills',
                    'Frontend developers wanting to master modern frameworks'
                ]),
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            [
                'organization_id' => 1, // Tech Academy
                'instructor_id' => 4, // Dr. James Wilson
                'category_id' => 3, // Artificial Intelligence
                'title' => 'Machine Learning with Python: Complete Guide',
                'slug' => 'machine-learning-python-complete-guide',
                'description' => 'Learn machine learning from scratch using Python, TensorFlow, and scikit-learn. Build real-world ML models and AI applications.',
                'content' => 'Comprehensive machine learning course covering supervised and unsupervised learning, neural networks, deep learning, and practical applications. Work with real datasets and build ML projects.',
                'thumbnail' => 'images/demo/courses/machine-learning.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 99.99,
                'is_free' => false,
                'level' => 'intermediate',
                'language' => 'en',
                'duration_minutes' => 2700, // 45 hours
                'requirements' => json_encode([
                    'Basic Python programming knowledge',
                    'Understanding of basic mathematics (algebra, statistics)',
                    'Computer with good specifications for ML tasks'
                ]),
                'what_you_will_learn' => json_encode([
                    'Understand machine learning fundamentals',
                    'Implement ML algorithms with scikit-learn',
                    'Build neural networks with TensorFlow',
                    'Work with real-world datasets',
                    'Deploy ML models to production',
                    'Apply ML to solve business problems'
                ]),
                'target_audience' => json_encode([
                    'Python developers interested in ML',
                    'Data scientists wanting to expand skills',
                    'Students and researchers in machine learning'
                ]),
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            
            // Business School Courses
            [
                'organization_id' => 2, // Business School
                'instructor_id' => 6, // Prof. Robert Johnson
                'category_id' => 5, // Business Management
                'title' => 'Strategic Business Management: MBA Essentials',
                'slug' => 'strategic-business-management-mba-essentials',
                'description' => 'Learn core MBA concepts including strategic planning, leadership, finance, and marketing. Develop skills needed for senior management positions.',
                'content' => 'Comprehensive business management course covering strategic thinking, organizational behavior, financial analysis, and market dynamics. Learn from real case studies of successful companies.',
                'thumbnail' => 'images/demo/courses/strategic-management.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 129.99,
                'is_free' => false,
                'level' => 'intermediate',
                'language' => 'en',
                'duration_minutes' => 3000, // 50 hours
                'requirements' => json_encode([
                    'Basic business understanding helpful',
                    'Analytical thinking skills',
                    'Interest in business strategy and management'
                ]),
                'what_you_will_learn' => json_encode([
                    'Develop strategic thinking capabilities',
                    'Understand organizational behavior and leadership',
                    'Master financial analysis and budgeting',
                    'Learn marketing strategies and market analysis',
                    'Make data-driven business decisions',
                    'Lead teams and organizations effectively'
                ]),
                'target_audience' => json_encode([
                    'Business professionals seeking advancement',
                    'Entrepreneurs and startup founders',
                    'MBA students and business graduates',
                    'Managers wanting to develop strategic skills'
                ]),
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 4,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            [
                'organization_id' => 2, // Business School
                'instructor_id' => 6, // Prof. Robert Johnson
                'category_id' => 6, // Digital Marketing
                'title' => 'Digital Marketing Mastery: Complete Course',
                'slug' => 'digital-marketing-mastery-complete-course',
                'description' => 'Master digital marketing including SEO, social media, content marketing, PPC, and analytics. Build comprehensive marketing strategies.',
                'content' => 'Learn all aspects of digital marketing from search engine optimization to social media marketing. Create effective campaigns and measure their success with analytics tools.',
                'thumbnail' => 'images/demo/courses/digital-marketing.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 89.99,
                'is_free' => false,
                'level' => 'beginner',
                'language' => 'en',
                'duration_minutes' => 2100, // 35 hours
                'requirements' => json_encode([
                    'Basic computer skills',
                    'Understanding of social media platforms',
                    'Interest in marketing and business growth'
                ]),
                'what_you_will_learn' => json_encode([
                    'Master SEO and rank websites on Google',
                    'Create effective social media marketing campaigns',
                    'Develop content marketing strategies',
                    'Run successful PPC advertising campaigns',
                    'Use analytics to measure marketing success',
                    'Build comprehensive digital marketing strategies'
                ]),
                'target_audience' => json_encode([
                    'Marketing professionals wanting digital skills',
                    'Business owners and entrepreneurs',
                    'Students interested in marketing careers',
                    'Anyone wanting to learn digital marketing'
                ]),
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 5,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            
            // Creative Arts Courses
            [
                'organization_id' => 3, // Creative Arts
                'instructor_id' => 7, // Maria Garcia
                'category_id' => 8, // Graphic Design
                'title' => 'Graphic Design Masterclass: From Basics to Professional',
                'slug' => 'graphic-design-masterclass-basics-to-professional',
                'description' => 'Learn graphic design fundamentals, Adobe Creative Suite, typography, color theory, and create professional designs for print and digital media.',
                'content' => 'Complete graphic design course covering design principles, Adobe Photoshop, Illustrator, InDesign, and modern design tools. Build a professional portfolio.',
                'thumbnail' => 'images/demo/courses/graphic-design.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 79.99,
                'is_free' => false,
                'level' => 'beginner',
                'language' => 'en',
                'duration_minutes' => 2400, // 40 hours
                'requirements' => json_encode([
                    'Computer with Adobe Creative Suite access',
                    'Basic computer skills',
                    'Creative interest and willingness to practice'
                ]),
                'what_you_will_learn' => json_encode([
                    'Master design principles and theory',
                    'Create designs in Adobe Photoshop and Illustrator',
                    'Understand typography and color theory',
                    'Design for both print and digital media',
                    'Build a professional design portfolio',
                    'Work with clients on real design projects'
                ]),
                'target_audience' => json_encode([
                    'Aspiring graphic designers',
                    'Artists wanting to learn digital design',
                    'Marketing professionals wanting design skills',
                    'Anyone interested in graphic design'
                ]),
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 6,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            [
                'organization_id' => 3, // Creative Arts
                'instructor_id' => 7, // Maria Garcia
                'category_id' => 9, // Digital Art
                'title' => 'Digital Illustration and Concept Art',
                'slug' => 'digital-illustration-concept-art',
                'description' => 'Master digital illustration techniques, character design, concept art, and create stunning artwork for games, films, and publications.',
                'content' => 'Learn digital illustration from basics to advanced techniques. Master character design, environment art, and create professional concept art.',
                'thumbnail' => 'images/demo/courses/digital-illustration.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 69.99,
                'is_free' => false,
                'level' => 'intermediate',
                'language' => 'en',
                'duration_minutes' => 1800, // 30 hours
                'requirements' => json_encode([
                    'Basic drawing skills helpful',
                    'Digital art tablet and software',
                    'Understanding of art fundamentals'
                ]),
                'what_you_will_learn' => json_encode([
                    'Master digital illustration techniques',
                    'Create compelling character designs',
                    'Design environments and backgrounds',
                    'Develop concept art for games and films',
                    'Build a professional illustration portfolio',
                    'Work with industry-standard digital art tools'
                ]),
                'target_audience' => json_encode([
                    'Artists wanting to go digital',
                    'Illustrators seeking professional skills',
                    'Game developers needing art skills',
                    'Anyone interested in digital art'
                ]),
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 7,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
            [
                'organization_id' => 3, // Creative Arts
                'instructor_id' => 7, // Maria Garcia
                'category_id' => 10, // Photography
                'title' => 'Professional Photography: Complete Course',
                'slug' => 'professional-photography-complete-course',
                'description' => 'Master photography fundamentals, composition, lighting, editing, and build a professional photography business.',
                'content' => 'Complete photography course covering camera settings, composition techniques, lighting, post-processing, and business aspects of photography.',
                'thumbnail' => 'images/demo/courses/photography.jpg',
                'preview_video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'price' => 59.99,
                'is_free' => false,
                'level' => 'beginner',
                'language' => 'en',
                'duration_minutes' => 1500, // 25 hours
                'requirements' => json_encode([
                    'DSLR or mirrorless camera recommended',
                    'Basic computer skills for editing',
                    'Interest in photography and visual arts'
                ]),
                'what_you_will_learn' => json_encode([
                    'Master camera settings and controls',
                    'Understand composition and lighting',
                    'Edit photos professionally',
                    'Build a photography portfolio',
                    'Start a photography business',
                    'Shoot various photography genres'
                ]),
                'target_audience' => json_encode([
                    'Photography enthusiasts',
                    'Artists wanting photography skills',
                    'People wanting to start photography business',
                    'Anyone interested in visual storytelling'
                ]),
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 8,
                'settings' => json_encode([
                    'certificate_available' => true,
                    'lifetime_access' => true,
                    'mobile_app_access' => true,
                    'download_resources' => true,
                    'community_access' => true
                ]),
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
