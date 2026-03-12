@extends('zenithalms.layouts.app')

@section('title', 'Virtual Classes - ZenithaLMS')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Virtual Classes</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                Join interactive live sessions with VR support and AI-powered features
            </p>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white border-b sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search virtual classes..." 
                           class="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <span class="material-icons-round absolute left-3 top-2.5 text-neutral-400">search</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <select name="course_id" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Status</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="live" {{ request('status') == 'live' ? 'selected' : '' }}>Live Now</option>
                    <option value="ended" {{ request('status') == 'ended' ? 'selected' : '' }}>Ended</option>
                </select>

                <select name="platform" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Platforms</option>
                    <option value="zoom" {{ request('platform') == 'zoom' ? 'selected' : '' }}>Zoom</option>
                    <option value="teams" {{ request('platform') == 'teams' ? 'selected' : '' }}>Teams</option>
                    <option value="google_meet" {{ request('platform') == 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                    <option value="custom" {{ request('platform') == 'custom' ? 'selected' : '' }}>Custom</option>
                </select>

                <input type="date" 
                       name="date" 
                       value="{{ request('date') }}"
                       class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
            </div>
        </div>
    </div>
</div>

<!-- Live Classes Alert -->
@if($virtualClasses->where('status', 'live')->count() > 0)
<div class="bg-red-50 border-l-4 border-red-400 p-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center">
            <span class="material-icons-round text-red-400 mr-2">live_tv</span>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-red-800">
                    {{ $virtualClasses->where('status', 'live')->count() }} classes are live now!
                </h3>
                <div class="mt-2 text-sm text-red-700">
                    @foreach($virtualClasses->where('status', 'live')->take(3) as $liveClass)
                        <a href="{{ route('zenithalms.virtual-class.join', $liveClass->id) }}" 
                           class="font-medium underline hover:text-red-900">
                            {{ $liveClass->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Virtual Classes Grid -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900 mb-2">Upcoming Classes</h2>
        <p class="text-neutral-600">{{ $virtualClasses->total() }} classes available</p>
    </div>

    @if($virtualClasses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($virtualClasses as $virtualClass)
                <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Class Header -->
                    <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-icons-round text-4xl text-primary-300">videocam</span>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 bg-{{ 
                                $virtualClass->status === 'live' ? 'red' : 
                                ($virtualClass->status === 'scheduled' ? 'blue' : 'gray') 
                            }}-500 text-white text-xs font-bold rounded-full animate-pulse">
                                {{ strtoupper($virtualClass->status) }}
                            </span>
                        </div>

                        <!-- Platform Badge -->
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 bg-neutral-800 text-white text-xs font-bold rounded-full">
                                {{ strtoupper($virtualClass->platform) }}
                            </span>
                        </div>

                        <!-- VR Badge -->
                        @if($virtualClass->ai_analysis && $virtualClass->ai_analysis['vr_compatibility']['compatible'])
                            <div class="absolute bottom-3 left-3">
                                <span class="px-3 py-1 bg-accent-purple text-white text-xs font-bold rounded-full">
                                    VR READY
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Class Content -->
                    <div class="p-6">
                        <!-- Course -->
                        @if($virtualClass->course)
                            <div class="mb-3">
                                <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                    {{ $virtualClass->course->title }}
                                </span>
                            </div>
                        @endif

                        <!-- Title -->
                        <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                            {{ $virtualClass->title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-neutral-600 text-sm mb-4 line-clamp-3">
                            {{ Str::limit(strip_tags($virtualClass->description), 100) }}
                        </p>

                        <!-- Instructor -->
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="material-icons-round text-primary-600 text-sm">person</span>
                            </div>
                            <span class="text-sm text-neutral-600">{{ $virtualClass->instructor->name }}</span>
                        </div>

                        <!-- Schedule Info -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <div class="text-sm font-bold text-primary-600">{{ $virtualClass->scheduled_at->format('M d') }}</div>
                                <div class="text-xs text-neutral-500">{{ $virtualClass->scheduled_at->format('H:i') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-bold text-primary-600">{{ $virtualClass->duration_minutes }}</div>
                                <div class="text-xs text-neutral-500">Minutes</div>
                            </div>
                        </div>

                        <!-- Participants -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-neutral-600 mb-1">
                                <span>Participants</span>
                                <span>{{ $virtualClass->current_participants }}/{{ $virtualClass->max_participants }}</span>
                            </div>
                            <div class="w-full bg-neutral-200 rounded-full h-2">
                                <div class="bg-primary-500 h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ ($virtualClass->current_participants / $virtualClass->max_participants) * 100 }}%"></div>
                            </div>
                        </div>

                        <!-- AI Insights -->
                        @if($virtualClass->ai_analysis)
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-icons-round text-accent-purple text-sm">psychology</span>
                                    <span class="text-sm font-medium text-accent-purple">AI Insights</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        {{ $virtualClass->ai_analysis['difficulty_level'] ?? 'N/A' }}
                                    </span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                        {{ $virtualClass->ai_analysis['estimated_engagement'] ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            @if($virtualClass->status === 'live')
                                @if(auth()->check())
                                    <a href="{{ route('zenithalms.virtual-class.join', $virtualClass->id) }}" 
                                       class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors text-center animate-pulse">
                                        <span class="material-icons-round text-sm mr-2">live_tv</span>
                                        Join Now
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors text-center">
                                        <span class="material-icons-round text-sm mr-2">login</span>
                                        Login to Join
                                    </a>
                                @endif
                            @elseif($virtualClass->status === 'scheduled')
                                @if(auth()->check())
                                    <button onclick="scheduleReminder({{ $virtualClass->id }})" 
                                            class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                                        <span class="material-icons-round text-sm mr-2">notification_add</span>
                                        Set Reminder
                                    </button>
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                                        <span class="material-icons-round text-sm mr-2">login</span>
                                        Login to Join
                                    </a>
                                @endif
                            @else
                                @if(auth()->check())
                                    <a href="{{ route('zenithalms.virtual-class.recording', $virtualClass->id) }}" 
                                       class="flex-1 px-4 py-2 bg-neutral-600 text-white rounded-xl font-semibold hover:bg-neutral-700 transition-colors text-center">
                                        <span class="material-icons-round text-sm mr-2">play_circle</span>
                                        Watch Recording
                                    </a>
                                @else
                                    <button disabled 
                                            class="flex-1 px-4 py-2 bg-neutral-300 text-neutral-500 rounded-xl font-semibold cursor-not-allowed text-center">
                                        <span class="material-icons-round text-sm mr-2">block</span>
                                        Class Ended
                                    </button>
                                @endif
                            @endif
                            
                            <a href="{{ route('zenithalms.virtual-class.show', $virtualClass->id) }}" 
                               class="px-4 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors">
                                <span class="material-icons-round text-sm">info</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $virtualClasses->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">videocam</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No virtual classes found</h3>
            <p class="text-neutral-600 mb-6">Check back later for upcoming classes</p>
            <a href="{{ route('zenithalms.virtual-class.index') }}" 
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse All Classes
            </a>
        </div>
    @endif
</div>

<!-- AI Recommendations Section -->
@if(auth()->check())
<div class="bg-neutral-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">AI Recommendations</h2>
            <p class="text-neutral-600">Virtual classes recommended based on your interests and schedule</p>
        </div>
        
        <div id="ai-recommendations" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Recommendations will be loaded here -->
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// ZenithaLMS: Schedule reminder functionality
function scheduleReminder(classId) {
    fetch(`/zenithalms/virtual-class/reminder/${classId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Reminder set successfully!', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// ZenithaLMS: Load AI recommendations
function loadRecommendations() {
    fetch('/zenithalms/virtual-class/recommendations')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-recommendations');
            if (data.recommendations && data.recommendations.length > 0) {
                container.innerHTML = data.recommendations.map(virtualClass => `
                    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="material-icons-round text-4xl text-primary-300">videocam</span>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 bg-${virtualClass.status === 'live' ? 'red' : (virtualClass.status === 'scheduled' ? 'blue' : 'gray')}-500 text-white text-xs font-bold rounded-full">
                                    ${virtualClass.status}
                                </span>
                            </div>
                            ${virtualClass.vr_compatible ? '<div class="absolute bottom-3 left-3"><span class="px-3 py-1 bg-accent-purple text-white text-xs font-bold rounded-full">VR READY</span></div>' : ''}
                        </div>
                        <div class="p-6">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2">${virtualClass.title}</h3>
                            <p class="text-neutral-600 text-xs mb-3 line-clamp-2">${virtualClass.description}</p>
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-primary-600">${virtualClass.scheduled_at}</div>
                                    <div class="text-xs text-neutral-500">Date</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-bold text-primary-600">${virtualClass.duration_minutes}</div>
                                    <div class="text-xs text-neutral-500">Minutes</div>
                                </div>
                            </div>
                            <a href="${route('zenithalms.virtual-class.show', virtualClass.id)}" 
                               class="w-full px-3 py-2 bg-primary-500 text-white rounded-lg text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                                View Details
                            </a>
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

// ZenithaLMS: Check for live classes periodically
function checkLiveClasses() {
    fetch('/zenithalms/virtual-class/live-check')
        .then(response => response.json())
        .then(data => {
            if (data.live_classes > 0) {
                // Show notification for new live classes
                showNotification(`${data.live_classes} classes are live now!`, 'info');
            }
        })
        .catch(error => {
            console.error('Error checking live classes:', error);
        });
}

// ZenithaLMS: Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load recommendations if user is authenticated
    if (document.getElementById('ai-recommendations')) {
        loadRecommendations();
    }
    
    // Check for live classes every 30 seconds
    setInterval(checkLiveClasses, 30000);
});
</script>
@endsection
