@extends('zenithalms.layouts.app')

@section('title', 'Instructor Dashboard - ZenithaLMS')

@section('content')
<!-- Dashboard Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Instructor Dashboard</h1>
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
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ auth()->user()->courses()->count() }}</div>
                <div class="text-sm text-neutral-600">My Courses</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ auth()->user()->courses()->where('is_published', true)->count() }}</div>
                <div class="text-sm text-neutral-600">Published</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-accent-purple">{{ auth()->user()->enrollments()->count() }}</div>
                <div class="text-sm text-neutral-600">Total Students</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ auth()->user()->virtualClasses()->count() }}</div>
                <div class="text-sm text-neutral-600">Virtual Classes</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent Activity -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Recent Activity</h2>
                    <a href="{{ route('zenithalms.instructor.activity') }}" class="text-primary-600 hover:text-primary-800 font-medium">
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
                                    <div class="font-medium text-neutral-900">Created new course</div>
                                    <div class="text-sm text-neutral-600">Advanced Web Development</div>
                                    <div class="text-xs text-neutral-500 mt-1">2 hours ago</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-green-600 text-sm">videocam</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">Scheduled virtual class</div>
                                    <div class="text-sm text-neutral-600">JavaScript Workshop</div>
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
                                    <div class="font-medium text-neutral-900">Created quiz</div>
                                    <div class="text-sm text-neutral-600">React Fundamentals</div>
                                    <div class="text-xs text-neutral-500 mt-1">1 day ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- My Courses -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">My Courses</h2>
                    <a href="{{ route('zenithalms.instructor.courses') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $courses = auth()->user()->courses()->take(4)->get();
                    @endphp
                    
                    @foreach($courses as $course)
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
                                
                                <!-- Status Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 bg-{{ 
                                        $course->is_published ? 'green' : 'yellow' 
                                    }}-100 text-{{ 
                                        $course->is_published ? 'green' : 'yellow' 
                                    }}-800 text-xs font-semibold rounded-full">
                                        {{ $course->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <h3 class="font-bold text-lg mb-2">{{ $course->title }}</h3>
                                
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                        <span>Students</span>
                                        <span>{{ $course->enrollments()->count() }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                        <span>Lessons</span>
                                        <span>{{ $course->lessons()->count() }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-neutral-600">
                                        <span>Price</span>
                                        <span>{{ $course->is_free ? 'Free' : '$' . $course->price }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="{{ route('zenithalms.instructor.course.edit', $course->id) }}" 
                                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                        Edit
                                    </a>
                                    <a href="{{ route('zenithalms.courses.show', $course->slug) }}" 
                                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Virtual Classes -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Virtual Classes</h2>
                    <a href="{{ route('zenithalms.instructor.virtual-classes') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $virtualClasses = auth()->user()->virtualClasses()->take(4)->get();
                    @endphp
                    
                    @foreach($virtualClasses as $virtualClass)
                        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                            <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20">
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="material-icons-round text-3xl text-primary-300">videocam</span>
                                </div>
                                
                                <!-- Status Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 bg-{{ 
                                        $virtualClass->status === 'live' ? 'red' : 
                                        ($virtualClass->status === 'scheduled' ? 'blue' : 'gray') 
                                    }}-100 text-{{ 
                                        $virtualClass->status === 'live' ? 'red' : 
                                        ($virtualClass->status === 'scheduled' ? 'blue' : 'gray') 
                                    }}-800 text-xs font-semibold rounded-full">
                                        {{ strtoupper($virtualClass->status) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <h3 class="font-bold text-lg mb-2">{{ $virtualClass->title }}</h3>
                                
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                        <span>Scheduled</span>
                                        <span>{{ $virtualClass->scheduled_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                        <span>Duration</span>
                                        <span>{{ $virtualClass->duration_minutes }} min</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-neutral-600">
                                        <span>Participants</span>
                                        <span>{{ $virtualClass->current_participants }}/{{ $virtualClass->max_participants }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="{{ route('zenithalms.instructor.virtual-class.edit', $virtualClass->id) }}" 
                                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                        Edit
                                    </a>
                                    <a href="{{ route('zenithalms.virtual-class.show', $virtualClass->id) }}" 
                                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('zenithalms.instructor.course.create') }}" 
                       class="w-full px-4 py-3 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">add</span>
                        Create Course
                    </a>
                    
                    <a href="{{ route('zenithalms.instructor.virtual-class.create') }}" 
                       class="w-full px-4 py-3 bg-accent-purple text-white rounded-lg font-semibold hover:bg-accent-purple/90 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">videocam</span>
                        Schedule Class
                    </a>
                    
                    <a href="{{ route('zenithalms.instructor.quiz.create') }}" 
                       class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">quiz</span>
                        Create Quiz
                    </a>
                    
                    <a href="{{ route('zenithalms.instructor.assignments') }}" 
                       class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">assignment</span>
                        Assignments
                    </a>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Performance Stats</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Total Revenue</span>
                            <span class="font-medium">${{ number_format(auth()->user()->getTotalRevenue(), 2) }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Active Students</span>
                            <span class="font-medium">{{ auth()->user()->getActiveStudentsCount() }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Completion Rate</span>
                            <span class="font-medium">{{ number_format(auth()->user()->getCompletionRate(), 1) }}%</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Average Rating</span>
                            <span class="font-medium">{{ number_format(auth()->user()->getAverageRating(), 1) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Engagement -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Student Engagement</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Course Views</span>
                            <span class="font-medium">{{ auth()->user()->getCourseViews() }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Quiz Attempts</span>
                            <span class="font-medium">{{ auth()->user()->getQuizAttempts() }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Forum Posts</span>
                            <span class="font-medium">{{ auth()->user()->getForumPosts() }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm text-neutral-600 mb-1">
                            <span>Assignment Submissions</span>
                            <span class="font-medium">{{ auth()->user()->getAssignmentSubmissions() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Classes -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Upcoming Classes</h3>
                <div class="space-y-3">
                    @php
                        $upcomingClasses = auth()->user()->virtualClasses()
                            ->where('status', 'scheduled')
                            ->where('scheduled_at', '>', now())
                            ->orderBy('scheduled_at')
                            ->take(3)
                            ->get();
                    @endphp
                    
                    @if($upcomingClasses->count() > 0)
                        @foreach($upcomingClasses as $class)
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="material-icons-round text-primary-600 text-sm">videocam</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900 text-sm">{{ $class->title }}</div>
                                    <div class="text-xs text-neutral-600">{{ $class->scheduled_at->format('M d, g:i A') }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-neutral-600 text-sm">No upcoming classes</p>
                    @endif
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Recent Notifications</h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-round text-green-600 text-sm">check_circle</span>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-neutral-900 text-sm">New enrollment</div>
                            <div class="text-xs text-neutral-600">Student enrolled in Web Development</div>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-round text-blue-600 text-sm">quiz</span>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-neutral-900 text-sm">Quiz completed</div>
                            <div class="text-xs text-neutral-600">5 students completed React quiz</div>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-round text-yellow-600 text-sm">assignment</span>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-neutral-900 text-sm">Assignment submitted</div>
                            <div class="text-xs text-neutral-600">3 assignments submitted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Instructor Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh stats every 30 seconds
    setInterval(function() {
        refreshStats();
    }, 30000);
    
    // Initialize charts
    initializeCharts();
});

function refreshStats() {
    // Refresh dashboard stats
    fetch('/zenithalms/instructor/api/stats')
        .then(response => response.json())
        .then(data => {
            // Update stats display
            updateStatsDisplay(data);
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

function updateStatsDisplay(data) {
    // Update course count
    const courseCount = document.querySelector('.text-primary-600');
    if (courseCount) {
        courseCount.textContent = data.courses_count;
    }
    
    // Update published courses count
    const publishedCount = document.querySelector('.text-green-600');
    if (publishedCount) {
        publishedCount.textContent = data.published_courses_count;
    }
    
    // Update student count
    const studentCount = document.querySelector('.text-accent-purple');
    if (studentCount) {
        studentCount.textContent = data.total_students;
    }
    
    // Update virtual classes count
    const virtualClassCount = document.querySelector('.text-yellow-600');
    if (virtualClassCount) {
        virtualClassCount.textContent = data.virtual_classes_count;
    }
}

function initializeCharts() {
    // Initialize performance chart
    const performanceCtx = document.getElementById('performance-chart');
    if (performanceCtx) {
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [1200, 1900, 3000, 5000, 4200, 6000],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Initialize engagement chart
    const engagementCtx = document.getElementById('engagement-chart');
    if (engagementCtx) {
        new Chart(engagementCtx, {
            type: 'bar',
            data: {
                labels: ['Courses', 'Quizzes', 'Assignments', 'Forum'],
                datasets: [{
                    label: 'Engagement',
                    data: [85, 92, 78, 65],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
}

// ZenithaLMS: Real-time notifications
function checkNotifications() {
    fetch('/zenithalms/instructor/api/notifications')
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                // Show notification badge
                const notificationBadge = document.querySelector('.notification-badge');
                if (notificationBadge) {
                    notificationBadge.textContent = data.notifications.length;
                    notificationBadge.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
}

// Check notifications every 30 seconds
setInterval(checkNotifications, 30000);

// ZenithaLMS: Show notification
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        type === 'info' ? 'bg-blue-500' : 'bg-gray-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, duration);
}
</script>
@endsection
