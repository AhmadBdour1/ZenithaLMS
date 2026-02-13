@extends('zenithalms.layouts.app')

@section('title', 'Student Dashboard - ZenithaLMS')

@section('content')
<!-- Dashboard Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Student Dashboard</h1>
                <p class="text-xl text-primary-100">Welcome back, {{ auth()->user()->name }}!</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold">{{ now()->format('l, F j, Y') }}</div>
                <div class="text-primary-100">{{ now()->format('g:i A') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-neutral-200 hover:shadow-lg transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-primary-600 text-xl">school</span>
                </div>
                <span class="text-green-500 text-sm font-medium">+12%</span>
            </div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['enrolled_courses'] }}</div>
            <div class="text-sm text-neutral-600">Enrolled Courses</div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-neutral-200 hover:shadow-lg transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-green-600 text-xl">check_circle</span>
                </div>
                <span class="text-green-500 text-sm font-medium">+8%</span>
            </div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['completed_courses'] }}</div>
            <div class="text-sm text-neutral-600">Completed Courses</div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-neutral-200 hover:shadow-lg transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-accent-purple rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-white text-xl">psychology</span>
                </div>
                <span class="text-green-500 text-sm font-medium">+15%</span>
            </div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['progress_summary']['average_progress'] }}%</div>
            <div class="text-sm text-neutral-600">Average Progress</div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-neutral-200 hover:shadow-lg transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-yellow-600 text-xl">emoji_events</span>
                </div>
                <span class="text-green-500 text-sm font-medium">+5</span>
            </div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['unread_notifications'] }}</div>
            <div class="text-sm text-neutral-600">Certificates Earned</div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent Activity -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Recent Activity</h2>
                    <a href="{{ route('zenithalms.student.activity') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="divide-y divide-neutral-200">
                        <!-- Activity items would be loaded here -->
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-primary-600 text-sm">school</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">Started new course</div>
                                    <div class="text-sm text-neutral-600">Advanced Web Development</div>
                                    <div class="text-xs text-neutral-500 mt-1">2 hours ago</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-green-600 text-sm">check_circle</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">Completed lesson</div>
                                    <div class="text-sm text-neutral-600">Introduction to JavaScript</div>
                                    <div class="text-xs text-neutral-500 mt-1">5 hours ago</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-accent-purple rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-white text-sm">quiz</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">Passed quiz</div>
                                    <div class="text-sm text-neutral-600">JavaScript Basics - Score: 85%</div>
                                    <div class="text-xs text-neutral-500 mt-1">1 day ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Current Courses -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Current Courses</h2>
                    <a href="{{ route('zenithalms.student.courses') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $currentCourses = $stats['current_courses'] ?? [];
                    @endphp
                    
                    @foreach($currentCourses as $course)
                        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                            <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20">
                                @if($course->thumbnail)
                                    <img src="{{ $course->getThumbnailUrl() }}" 
                                         alt="{{ $course->title }}" 
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="material-icons-round text-3xl text-primary-300">school</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="p-6">
                                <h3 class="font-bold text-lg mb-2">{{ $course->title }}</h3>
                                
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                        <span>Progress</span>
                                        <span>{{ $course->pivot->progress_percentage ?? 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all duration-300" 
                                             style="width: {{ $course->pivot->progress_percentage ?? 0 }}%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="{{ route('zenithalms.courses.show', $course->slug) }}" 
                                       class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center">
                                        Continue
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- AI Recommendations -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">AI Recommendations</h2>
                    <button onclick="refreshRecommendations()" class="text-primary-600 hover:text-primary-800 font-medium">
                        <span class="material-icons-round text-sm mr-1">refresh</span>
                        Refresh
                    </button>
                </div>
                
                <div id="ai-recommendations" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Recommendations will be loaded here -->
                </div>
            </section>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Learning Profile -->
            <section>
                <h2 class="text-xl font-bold text-neutral-900 mb-4">Learning Profile</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                <span>Learning Style</span>
                                <span class="font-medium">{{ auth()->user()->learning_profile['learning_style'] ?? 'Visual' }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                <span>Skill Level</span>
                                <span class="font-medium">{{ auth()->user()->learning_profile['skill_level'] ?? 'Beginner' }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                <span>Preferred Duration</span>
                                <span class="font-medium">{{ auth()->user()->learning_profile['preferred_duration'] ?? 30 }} min</span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-neutral-600 mb-2">Interests</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach(auth()->user()->learning_profile['interests'] ?? ['Programming', 'Design'] as $interest)
                                    <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                        {{ $interest }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Upcoming Events -->
            <section>
                <h2 class="text-xl font-bold text-neutral-900 mb-4">Upcoming Events</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-red-600 text-sm">videocam</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900">Live Session: Advanced JavaScript</div>
                                <div class="text-sm text-neutral-600">Today, 3:00 PM</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-blue-600 text-sm">quiz</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900">Quiz: React Fundamentals</div>
                                <div class="text-sm text-neutral-600">Tomorrow, 10:00 AM</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-green-600 text-sm">assignment</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900">Assignment Due: CSS Project</div>
                                <div class="text-sm text-neutral-600">Dec 15, 11:59 PM</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Achievements -->
            <section>
                <h2 class="text-xl font-bold text-neutral-900 mb-4">Recent Achievements</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-icons-round text-yellow-600 text-2xl">emoji_events</span>
                            </div>
                            <div class="text-xs font-medium text-neutral-900">First Course</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-icons-round text-blue-600 text-2xl">speed</span>
                            </div>
                            <div class="text-xs font-medium text-neutral-900">Fast Learner</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-icons-round text-green-600 text-2xl">star</span>
                            </div>
                            <div class="text-xs font-medium text-neutral-900">Quiz Master</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section>
                <h2 class="text-xl font-bold text-neutral-900 mb-4">Quick Actions</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="space-y-3">
                        <a href="{{ route('zenithalms.courses.index') }}" 
                           class="w-full px-4 py-3 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center block">
                            <span class="material-icons-round text-sm mr-2">school</span>
                            Browse Courses
                        </a>
                        
                        <a href="{{ route('zenithalms.quiz.index') }}" 
                           class="w-full px-4 py-3 bg-accent-purple text-white rounded-lg font-semibold hover:bg-accent-purple/90 transition-colors text-center block">
                            <span class="material-icons-round text-sm mr-2">quiz</span>
                            Take Quiz
                        </a>
                        
                        <a href="{{ route('zenithalms.forum.index') }}" 
                           class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center block">
                            <span class="material-icons-round text-sm mr-2">forum</span>
                            Join Forum
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Load AI recommendations
function loadRecommendations() {
    fetch('/zenithalms/api/student/recommendations')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-recommendations');
            if (data.recommendations && data.recommendations.length > 0) {
                container.innerHTML = data.recommendations.map(item => `
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-primary-600">${item.type === 'course' ? 'school' : 'quiz'}</span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-neutral-900 mb-1">${item.title}</h3>
                                    <p class="text-sm text-neutral-600 mb-3">${item.description}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-1 bg-${item.difficulty === 'easy' ? 'green' : (item.difficulty === 'medium' ? 'yellow' : 'red')}-100 text-${item.difficulty === 'easy' ? 'green' : (item.difficulty === 'medium' ? 'yellow' : 'red')}-800 text-xs font-semibold rounded-full">
                                            ${item.difficulty}
                                        </span>
                                        <a href="${item.url}" class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                                            View →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-neutral-600 col-span-full">No recommendations available yet</p>';
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
        });
}

// ZenithaLMS: Refresh recommendations
function refreshRecommendations() {
    const container = document.getElementById('ai-recommendations');
    container.innerHTML = '<p class="text-center text-neutral-600 col-span-full">Loading recommendations...</p>';
    loadRecommendations();
}

// ZenithaLMS: Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadRecommendations();
});
</script>
@endsection
