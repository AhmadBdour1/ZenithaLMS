@extends('zenithalms.layouts.app')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl font-bold mb-6">Welcome to ZenithaLMS</h1>
            <p class="text-xl mb-8">Transform your learning experience with AI-powered education platform</p>
            <div class="space-x-4">
                <a href="{{ route('login') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">Get Started</a>
                <a href="/courses" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">Browse Courses</a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">Why Choose ZenithaLMS?</h2>
            <p class="text-xl text-gray-600">Experience the future of learning with our innovative features</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-brain text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-4">AI-Powered Learning</h3>
                <p class="text-gray-600">Get personalized recommendations and intelligent tutoring with our advanced AI assistant</p>
            </div>
            
            <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-video text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-4">Virtual Classes</h3>
                <p class="text-gray-600">Join live interactive sessions with instructors and fellow students from anywhere</p>
            </div>
            
            <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-certificate text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-4">Certified Courses</h3>
                <p class="text-gray-600">Earn recognized certificates upon completion and advance your career</p>
            </div>
        </div>
    </div>
</section>

<!-- Popular Courses Section -->
<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">Popular Courses</h2>
            <p class="text-xl text-gray-600">Discover our most sought-after courses</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="h-48 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Web Development Bootcamp</h3>
                    <p class="text-gray-600 mb-4">Master modern web development with HTML, CSS, JavaScript, and popular frameworks</p>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-blue-600">$99</span>
                        <a href="/courses" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Enroll Now</a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="h-48 bg-gradient-to-r from-green-500 to-green-600"></div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Data Science Fundamentals</h3>
                    <p class="text-gray-600 mb-4">Learn data analysis, machine learning, and statistical modeling with Python</p>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-green-600">$149</span>
                        <a href="/courses" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Enroll Now</a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="h-48 bg-gradient-to-r from-purple-500 to-purple-600"></div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Digital Marketing Mastery</h3>
                    <p class="text-gray-600 mb-4">Master SEO, social media marketing, and digital advertising strategies</p>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-purple-600">$79</span>
                        <a href="/courses" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">Enroll Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20 bg-blue-600 text-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-bold mb-2">10,000+</div>
                <div class="text-blue-200">Active Students</div>
            </div>
            <div>
                <div class="text-4xl font-bold mb-2">500+</div>
                <div class="text-blue-200">Expert Instructors</div>
            </div>
            <div>
                <div class="text-4xl font-bold mb-2">1,000+</div>
                <div class="text-blue-200">Online Courses</div>
            </div>
            <div>
                <div class="text-4xl font-bold mb-2">95%</div>
                <div class="text-blue-200">Success Rate</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Ready to Start Your Learning Journey?</h2>
        <p class="text-xl text-gray-600 mb-8">Join thousands of students already learning with ZenithaLMS</p>
        <a href="{{ route('register') }}" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition text-lg">Sign Up for Free</a>
    </div>
</section>
@endsection
