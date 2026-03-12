@extends('zenithalms.layouts.app')

@section('title', 'ZenithaLMS - Reach Your Learning Peak')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-primary-50 via-white to-accent-purple/50">
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-10 w-72 h-72 bg-primary-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 floating"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-accent-purple/30 rounded-full mix-blend-multiply filter blur-xl opacity-70 floating" style="animation-delay: 2s;"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-accent-blue/20 rounded-full mix-blend-multiply filter blur-xl opacity-70 floating" style="animation-delay: 4s;"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary-100 rounded-full text-primary-700 font-semibold mb-6">
                    <span class="material-icons-round text-sm">auto_awesome</span>
                    AI-Powered Learning Platform
                </div>
                <h1 class="text-5xl md:text-7xl font-bold text-neutral-900 mb-6">
                    <span class="gradient-text">Reach Your</span><br>
                    Learning Peak
                </h1>
                <p class="text-xl text-neutral-600 max-w-3xl mx-auto mb-8">
                    Experience the future of education with our AI-powered adaptive learning platform. 
                    Personalized learning paths, intelligent tutoring, and comprehensive progress tracking.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <button class="px-8 py-4 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-all transform hover:scale-105 shadow-lg">
                    <span class="material-icons-round text-sm mr-2">rocket_launch</span>
                    Start Learning Free
                </button>
                <button class="px-8 py-4 bg-white text-primary-600 rounded-xl font-semibold hover:bg-primary-50 transition-all border border-primary-200">
                    <span class="material-icons-round text-sm mr-2">play_circle</span>
                    Watch Demo
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600 mb-2">50K+</div>
                    <div class="text-neutral-600">Active Students</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-accent-purple mb-2">500+</div>
                    <div class="text-neutral-600">Expert Courses</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-accent-green mb-2">95%</div>
                    <div class="text-neutral-600">Success Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-accent-blue mb-2">24/7</div>
                    <div class="text-neutral-600">AI Assistant</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-neutral-900 mb-4">
                Why Choose <span class="gradient-text">ZenithaLMS</span>
            </h2>
            <p class="text-xl text-neutral-600 max-w-3xl mx-auto">
                Discover the features that make our platform the ultimate learning experience
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-primary-50 to-white border border-primary-100 card-hover">
                <div class="w-14 h-14 bg-primary-500 rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">psychology</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">AI-Powered Learning</h3>
                <p class="text-neutral-600">
                    Our intelligent AI assistant adapts to your learning style and provides personalized recommendations.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-accent-purple/10 to-white border border-accent-purple/20 card-hover">
                <div class="w-14 h-14 bg-accent-purple rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">timeline</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">Adaptive Paths</h3>
                <p class="text-neutral-600">
                    Dynamic learning paths that adjust based on your progress, strengths, and areas for improvement.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-accent-green/10 to-white border border-accent-green/20 card-hover">
                <div class="w-14 h-14 bg-accent-green rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">analytics</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">Progress Tracking</h3>
                <p class="text-neutral-600">
                    Comprehensive analytics and insights to track your learning journey and celebrate achievements.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-accent-blue/10 to-white border border-accent-blue/20 card-hover">
                <div class="w-14 h-14 bg-accent-blue rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">groups</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">Expert Instructors</h3>
                <p class="text-neutral-600">
                    Learn from industry experts and experienced educators dedicated to your success.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-accent-yellow/10 to-white border border-accent-yellow/20 card-hover">
                <div class="w-14 h-14 bg-accent-yellow rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">workspace_premium</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">Certifications</h3>
                <p class="text-neutral-600">
                    Earn recognized certificates that validate your skills and boost your career opportunities.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="p-8 rounded-2xl bg-gradient-to-br from-accent-pink/10 to-white border border-accent-pink/20 card-hover">
                <div class="w-14 h-14 bg-accent-pink rounded-xl flex items-center justify-center mb-6">
                    <span class="material-icons-round text-white text-2xl">support_agent</span>
                </div>
                <h3 class="text-xl font-bold text-neutral-900 mb-4">24/7 Support</h3>
                <p class="text-neutral-600">
                    Get help whenever you need it with our round-the-clock support and AI assistant.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-primary-500 to-accent-purple">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-white mb-6">
            Ready to Transform Your Learning Journey?
        </h2>
        <p class="text-xl text-primary-100 mb-8">
            Join thousands of students who are already experiencing the future of education
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="px-8 py-4 bg-white text-primary-600 rounded-xl font-semibold hover:bg-primary-50 transition-all">
                <span class="material-icons-round text-sm mr-2">rocket_launch</span>
                Get Started Free
            </button>
            <button class="px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition-all">
                <span class="material-icons-round text-sm mr-2">info</span>
                Learn More
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Animate stats on scroll
const observerOptions = {
    threshold: 0.5,
    rootMargin: '0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'pulse-glow 2s infinite';
        }
    });
}, observerOptions);

document.querySelectorAll('.card-hover').forEach(card => {
    observer.observe(card);
});
</script>
@endpush
