@extends('zenithalms.layouts.app')

@section('title', 'Forum - ZenithaLMS')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Community Forum</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                Connect, learn, and share with our vibrant community
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
                           placeholder="Search discussions..." 
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

                <select name="category" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Categories</option>
                    <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>General</option>
                    <option value="discussion" {{ request('category') == 'discussion' ? 'selected' : '' }}>Discussion</option>
                    <option value="question" {{ request('category') == 'question' ? 'selected' : '' }}>Question</option>
                    <option value="announcement" {{ request('category') == 'announcement' ? 'selected' : '' }}>Announcement</option>
                    <option value="feedback" {{ request('category') == 'feedback' ? 'selected' : '' }}>Feedback</option>
                </select>

                <select name="sort" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="latest">Latest</option>
                    <option value="popular">Most Popular</option>
                    <option value="most_replies">Most Replies</option>
                    <option value="most_viewed">Most Viewed</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Forum Posts -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">Discussions</h2>
            <p class="text-neutral-600">{{ $forums->total() }} discussions available</p>
        </div>
        
        @if(auth()->check())
            <a href="{{ route('zenithalms.forum.create') }}" 
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                <span class="material-icons-round text-sm mr-2">add</span>
                New Discussion
            </a>
        @endif
    </div>

    @if($forums->count() > 0)
        <div class="space-y-4">
            @foreach($forums as $forum)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                    <!-- Forum Header -->
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <!-- Title and Category -->
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-neutral-900 hover:text-primary-600 transition-colors">
                                        <a href="{{ route('zenithalms.forum.show', $forum->id) }}">
                                            {{ $forum->title }}
                                        </a>
                                    </h3>
                                    
                                    <!-- Category Badge -->
                                    <span class="px-3 py-1 bg-{{ 
                                        $forum->category === 'announcement' ? 'red' : 
                                        ($forum->category === 'question' ? 'blue' : 
                                        ($forum->category === 'feedback' ? 'yellow' : 'green') 
                                    }}-100 text-{{ 
                                        $forum->category === 'announcement' ? 'red' : 
                                        ($forum->category === 'question' ? 'blue' : 
                                        ($forum->category === 'feedback' ? 'yellow' : 'green') 
                                    }}-800 text-xs font-semibold rounded-full">
                                        {{ ucfirst($forum->category) }}
                                    </span>
                                    
                                    <!-- Pinned Badge -->
                                    @if($forum->is_pinned)
                                        <span class="px-3 py-1 bg-accent-yellow text-white text-xs font-bold rounded-full">
                                            PINNED
                                        </span>
                                    @endif
                                </div>

                                <!-- Meta Information -->
                                <div class="flex items-center gap-4 text-sm text-neutral-600 mb-4">
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons-round text-sm">person</span>
                                        <span>{{ $forum->user->name }}</span>
                                    </div>
                                    
                                    @if($forum->course)
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">school</span>
                                            <span>{{ $forum->course->title }}</span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons-round text-sm">schedule</span>
                                        <span>{{ $forum->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <!-- Content Preview -->
                                <div class="text-neutral-700 mb-4 line-clamp-3">
                                    {{ Str::limit(strip_tags($forum->content), 200) }}
                                </div>

                                <!-- Stats and Actions -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-6 text-sm text-neutral-600">
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">comment</span>
                                            <span>{{ $forum->reply_count }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">visibility</span>
                                            <span>{{ $forum->view_count }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">thumb_up</span>
                                            <span>{{ $forum->like_count ?? 0 }}</span>
                                        </div>
                                        
                                        <!-- AI Sentiment -->
                                        @if($forum->ai_sentiment)
                                            <div class="flex items-center gap-1">
                                                <span class="material-icons-round text-sm">sentiment_satisfied</span>
                                                <span class="px-2 py-1 bg-{{ 
                                                    $forum->ai_sentiment['sentiment'] === 'positive' ? 'green' : 
                                                    ($forum->ai_sentiment['sentiment'] === 'negative' ? 'red' : 'gray') 
                                                }}-100 text-{{ 
                                                    $forum->ai_sentiment['sentiment'] === 'positive' ? 'green' : 
                                                    ($forum->ai_sentiment['sentiment'] === 'negative' ? 'red' : 'gray') 
                                                }}-800 text-xs font-semibold rounded-full">
                                                    {{ ucfirst($forum->ai_sentiment['sentiment']) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('zenithalms.forum.show', $forum->id) }}" 
                                           class="px-4 py-2 text-primary-600 hover:text-primary-800 font-medium transition-colors">
                                            View Discussion
                                        </a>
                                        
                                        @if(auth()->check())
                                            <button onclick="likeForum({{ $forum->id }})" 
                                                    class="p-2 text-neutral-600 hover:text-primary-600 transition-colors"
                                                    data-forum-id="{{ $forum->id }}"
                                                    data-liked="{{ $forum->isLikedBy(auth()->id()) ? 'true' : 'false' }}">
                                                <span class="material-icons-round text-sm">
                                                    {{ $forum->isLikedBy(auth()->id()) ? 'favorite' : 'favorite_border' }}
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $forums->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">forum</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No discussions found</h3>
            <p class="text-neutral-600 mb-6">Be the first to start a discussion!</p>
            @if(auth()->check())
                <a href="{{ route('zenithalms.forum.create') }}" 
                   class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">add</span>
                    Start Discussion
                </a>
            @else
                <a href="{{ route('login') }}" 
                   class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">login</span>
                    Login to Participate
                </a>
            @endif
        </div>
    @endif
</div>

<!-- AI Recommendations Section -->
@if(auth()->check())
<div class="bg-neutral-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">AI Recommendations</h2>
            <p class="text-neutral-600">Discussions you might be interested in based on your activity</p>
        </div>
        
        <div id="ai-recommendations" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Recommendations will be loaded here -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Like forum functionality
function likeForum(forumId) {
    const button = document.querySelector(`[data-forum-id="${forumId}"]`);
    const icon = button.querySelector('.material-icons-round');
    const isLiked = button.dataset.liked === 'true';
    
    fetch(`/zenithalms/forum/like/${forumId}`, {
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
            button.dataset.liked = data.liked ? 'true' : 'false';
            icon.textContent = data.liked ? 'favorite' : 'favorite_border';
            
            // Update like count
            const likeCount = button.closest('.flex').querySelector('.text-neutral-600 span:last-child');
            if (likeCount) {
                likeCount.textContent = data.like_count;
            }
            
            showNotification(data.message, data.liked ? 'success' : 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// ZenithaLMS: Load AI recommendations
function loadRecommendations() {
    fetch('/zenithalms/forum/recommendations')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-recommendations');
            if (data.recommendations && data.recommendations.length > 0) {
                container.innerHTML = data.recommendations.map(forum => `
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-bold text-neutral-900">
                                            <a href="${route('zenithalms.forum.show', forum.id)}" class="hover:text-primary-600 transition-colors">
                                                ${forum.title}
                                            </a>
                                        </h3>
                                        
                                        <span class="px-3 py-1 bg-${forum.category === 'announcement' ? 'red' : (forum.category === 'question' ? 'blue' : 'green')}-100 text-${forum.category === 'announcement' ? 'red' : (forum.category === 'question' ? 'blue' : 'green')}-800 text-xs font-semibold rounded-full">
                                            ${forum.category}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-4 text-sm text-neutral-600 mb-4">
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">person</span>
                                            <span>${forum.user.name}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">schedule</span>
                                            <span>${forum.created_at}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-neutral-700 line-clamp-3">
                                        ${forum.content.substring(0, 100)}...
                                    </div>
                                    
                                    <div class="flex items-center gap-6 text-sm text-neutral-600">
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">comment</span>
                                            <span>${forum.reply_count}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <span class="material-icons-round text-sm">visibility</span>
                                            <span>${forum.view_count}</span>
                                        </div>
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

// ZenithaLMS: Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load recommendations if user is authenticated
    if (document.getElementById('ai-recommendations')) {
        loadRecommendations();
    }
});
</script>
@endsection
