@extends('zenithalms.layouts.app')

@section('title', 'Quizzes - ZenithaLMS')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Interactive Quizzes</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                Test your knowledge with AI-powered adaptive quizzes
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
                           placeholder="Search quizzes..." 
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

                <select name="difficulty" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Levels</option>
                    <option value="easy" {{ request('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                    <option value="medium" {{ request('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="hard" {{ request('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- All Quizzes -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900 mb-2">Available Quizzes</h2>
        <p class="text-neutral-600">{{ $quizzes->total() }} quizzes available</p>
    </div>

    @if($quizzes->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($quizzes as $quiz)
                <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Quiz Header -->
                    <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-icons-round text-4xl text-primary-300">quiz</span>
                        </div>
                        
                        <!-- Difficulty Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 bg-{{ 
                                $quiz->difficulty_level === 'easy' ? 'green' : 
                                ($quiz->difficulty_level === 'medium' ? 'yellow' : 'red') 
                            }}-500 text-white text-xs font-bold rounded-full">
                                {{ ucfirst($quiz->difficulty_level) }}
                            </span>
                        </div>

                        <!-- Status Badge -->
                        @if($quiz->is_published)
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 bg-accent-green text-white text-xs font-bold rounded-full">
                                    Published
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Quiz Content -->
                    <div class="p-6">
                        <!-- Course -->
                        @if($quiz->course)
                            <div class="mb-3">
                                <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                    {{ $quiz->course->title }}
                                </span>
                            </div>
                        @endif

                        <!-- Title -->
                        <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                            {{ $quiz->title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-neutral-600 text-sm mb-4 line-clamp-3">
                            {{ Str::limit(strip_tags($quiz->description), 100) }}
                        </p>

                        <!-- Instructor -->
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="material-icons-round text-primary-600 text-sm">person</span>
                            </div>
                            <span class="text-sm text-neutral-600">{{ $quiz->createdBy->name }}</span>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <div class="text-lg font-bold text-primary-600">{{ $quiz->number_of_questions }}</div>
                                <div class="text-xs text-neutral-500">Questions</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-primary-600">{{ $quiz->time_limit_minutes ?? '∞' }}</div>
                                <div class="text-xs text-neutral-500">Minutes</div>
                            </div>
                        </div>

                        <!-- Progress Bar (if user has attempts) -->
                        @if(auth()->check())
                            <?php
                            $userAttempts = \App\Models\QuizAttempt::where('user_id', auth()->id())
                                ->where('quiz_id', $quiz->id)
                                ->orderBy('percentage', 'desc')
                                ->first();
                            ?>
                            @if($userAttempts)
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-neutral-600 mb-1">
                                        <span>Best Score</span>
                                        <span>{{ number_format($userAttempts->percentage, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all duration-300" 
                                             style="width: {{ $userAttempts->percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            @if(auth()->check())
                                @php
                                    $userAttempts = \App\Models\QuizAttempt::where('user_id', auth()->id())
                                        ->where('quiz_id', $quiz->id)
                                        ->count();
                                    $canAttempt = $userAttempts < $quiz->max_attempts;
                                    $hasInProgress = \App\Models\QuizAttempt::where('user_id', auth()->id())
                                        ->where('quiz_id', $quiz->id)
                                        ->where('status', 'in_progress')
                                        ->exists();
                                @endphp
                                
                                @if($hasInProgress)
                                    <a href="{{ route('zenithalms.quiz.attempt', $quiz->id) }}" 
                                       class="flex-1 px-4 py-2 bg-accent-purple text-white rounded-xl font-semibold hover:bg-accent-purple/90 transition-colors text-center">
                                        <span class="material-icons-round text-sm mr-2">play_arrow</span>
                                        Continue
                                    </a>
                                @elseif($canAttempt)
                                    <a href="{{ route('zenithalms.quiz.start', $quiz->id) }}" 
                                       class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                                        <span class="material-icons-round text-sm mr-2">play_arrow</span>
                                        Start Quiz
                                    </a>
                                @else
                                    <button disabled 
                                            class="flex-1 px-4 py-2 bg-neutral-300 text-neutral-500 rounded-xl font-semibold cursor-not-allowed text-center">
                                        <span class="material-icons-round text-sm mr-2">block</span>
                                        Max Attempts
                                    </button>
                                @endif
                                
                                <a href="{{ route('zenithalms.quiz.show', $quiz->id) }}" 
                                   class="px-4 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors">
                                    <span class="material-icons-round text-sm">info</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">login</span>
                                    Login to Start
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $quizzes->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">quiz</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No quizzes found</h3>
            <p class="text-neutral-600 mb-6">Try adjusting your search criteria or browse all quizzes</p>
            <a href="{{ route('zenithalms.quiz.index') }}" 
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse All Quizzes
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
            <p class="text-neutral-600">Personalized quiz recommendations based on your learning progress</p>
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
// ZenithaLMS: Load AI recommendations
function loadRecommendations() {
    fetch('/zenithalms/api/quiz-recommendations')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-recommendations');
            if (data.recommendations && data.recommendations.length > 0) {
                container.innerHTML = data.recommendations.map(quiz => `
                    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="material-icons-round text-4xl text-primary-300">quiz</span>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 bg-${quiz.difficulty_level === 'easy' ? 'green' : (quiz.difficulty_level === 'medium' ? 'yellow' : 'red')}-500 text-white text-xs font-bold rounded-full">
                                    ${quiz.difficulty_level}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2">${quiz.title}</h3>
                            <p class="text-neutral-600 text-xs mb-3 line-clamp-2">${quiz.description}</p>
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-primary-600">${quiz.number_of_questions}</div>
                                    <div class="text-xs text-neutral-500">Questions</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-bold text-primary-600">${quiz.time_limit_minutes || '∞'}</div>
                                    <div class="text-xs text-neutral-500">Minutes</div>
                                </div>
                            </div>
                            <a href="${route('zenithalms.quiz.show', quiz.id)}" 
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

// ZenithaLMS: Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load recommendations if user is authenticated
    if (document.getElementById('ai-recommendations')) {
        loadRecommendations();
    }
});
</script>
@endsection
