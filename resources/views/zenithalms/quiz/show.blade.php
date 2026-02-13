@extends('zenithalms.layouts.app')

@section('title', $quiz->title . ' - ZenithaLMS')

@section('content')
<!-- Quiz Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ $quiz->title }}</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">{{ $quiz->description }}</p>
        </div>
    </div>
</div>

<!-- Quiz Info -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $quiz->number_of_questions }}</div>
                <div class="text-sm text-neutral-600">Questions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $quiz->time_limit_minutes ?? '∞' }}</div>
                <div class="text-sm text-neutral-600">Minutes</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $quiz->passing_score }}%</div>
                <div class="text-sm text-neutral-600">Passing Score</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $quiz->max_attempts }}</div>
                <div class="text-sm text-neutral-600">Max Attempts</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Quiz Details -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Quiz Details</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-neutral-900 mb-2">Instructions</h3>
                            <p class="text-neutral-700">{{ $quiz->instructions ?? 'Read each question carefully and select the best answer.' }}</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-neutral-900 mb-2">Quiz Rules</h3>
                            <ul class="space-y-2 text-neutral-700">
                                <li class="flex items-start gap-2">
                                    <span class="material-icons-round text-primary-600 text-sm mt-1">check_circle</span>
                                    <span>You have {{ $quiz->time_limit_minutes ?? 'unlimited' }} minutes to complete</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="material-icons-round text-primary-600 text-sm mt-1">check_circle</span>
                                    <span>You need {{ $quiz->passing_score }}% to pass</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="material-icons-round text-primary-600 text-sm mt-1">check_circle</span>
                                    <span>You can attempt this quiz {{ $quiz->max_attempts }} times</span>
                                </li>
                                @if($quiz->shuffle_questions)
                                <li class="flex items-start gap-2">
                                    <span class="material-icons-round text-primary-600 text-sm mt-1">check_circle</span>
                                    <span>Questions will be shuffled</span>
                                </li>
                                @endif
                                @if($quiz->show_answers)
                                <li class="flex items-start gap-2">
                                    <span class="material-icons-round text-primary-600 text-sm mt-1">check_circle</span>
                                    <span>Answers will be shown after completion</span>
                                </li>
                                @endif
                            </ul>
                        </div>
                        
                        @if($quiz->course)
                        <div>
                            <h3 class="font-semibold text-neutral-900 mb-2">Related Course</h3>
                            <div class="flex items-center gap-3 p-3 bg-primary-50 rounded-lg">
                                <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="material-icons-round text-primary-600">school</span>
                                </div>
                                <div>
                                    <div class="font-semibold">{{ $quiz->course->title }}</div>
                                    <div class="text-sm text-neutral-600">{{ $quiz->course->description }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Your Attempts -->
            @if(auth()->check() && $userAttempts->count() > 0)
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Your Attempts</h2>
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Attempt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Percentage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-neutral-200">
                                @foreach($userAttempts as $attempt)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        #{{ $attempt->attempt_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        {{ $attempt->score ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        @if($attempt->percentage)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{
                                                $attempt->percentage >= $quiz->passing_score ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            }}">
                                                {{ number_format($attempt->percentage, 1) }}%
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{
                                            $attempt->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                            ($attempt->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')
                                        }}">
                                            {{ ucfirst($attempt->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        {{ $attempt->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        @if($attempt->status === 'in_progress')
                                            <a href="{{ route('zenithalms.quiz.attempt', $attempt->id) }}" 
                                               class="text-primary-600 hover:text-primary-900 font-medium">
                                                Continue
                                            </a>
                                        @elseif($attempt->status === 'completed')
                                            <a href="{{ route('zenithalms.quiz.result', $attempt->id) }}" 
                                               class="text-primary-600 hover:text-primary-900 font-medium">
                                                View Result
                                            </a>
                                        @else
                                            <span class="text-neutral-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            @endif

            <!-- Best Attempt -->
            @if($bestAttempt)
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Best Attempt</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <h3 class="font-semibold text-neutral-900 mb-2">Score</h3>
                                <div class="text-3xl font-bold text-primary-600">{{ $bestAttempt->score }}</div>
                                <div class="text-sm text-neutral-600">out of {{ $quiz->questions->sum('points') }} points</div>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="font-semibold text-neutral-900 mb-2">Percentage</h3>
                                <div class="text-3xl font-bold text-primary-600">{{ number_format($bestAttempt->percentage, 1) }}%</div>
                                <div class="text-sm text-neutral-600">{{ $bestAttempt->percentage >= $quiz->passing_score ? 'Passed' : 'Failed' }}</div>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="font-semibold text-neutral-900 mb-2">Time Taken</h3>
                                <div class="text-3xl font-bold text-primary-600">{{ $bestAttempt->time_taken_minutes ?? 'N/A' }}</div>
                                <div class="text-sm text-neutral-600">minutes</div>
                            </div>
                        </div>
                        
                        <div>
                            @if($bestAttempt->ai_insights)
                                <h3 class="font-semibold text-neutral-900 mb-2">AI Insights</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                            {{ $bestAttempt->ai_insights['performance_level'] ?? 'N/A' }}
                                        </span>
                                        <span class="text-sm text-neutral-600">Performance Level</span>
                                    </div>
                                    
                                    @if(isset($bestAttempt->ai_insights['strength_areas']))
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-900 mb-1">Strength Areas</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($bestAttempt->ai_insights['strength_areas'] as $area)
                                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                    {{ $area }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if(isset($bestAttempt->ai_insights['improvement_areas']))
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-900 mb-1">Areas to Improve</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($bestAttempt->ai_insights['improvement_areas'] as $area)
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                                    {{ $area }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Start Quiz Button -->
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
                    <div class="bg-white rounded-xl p-6 border border-neutral-200">
                        <h3 class="text-lg font-bold text-neutral-900 mb-4">Continue Quiz</h3>
                        <p class="text-neutral-600 mb-6">You have an attempt in progress. Continue from where you left off.</p>
                        <a href="{{ route('zenithalms.quiz.attempt', $quiz->id) }}" 
                           class="w-full px-6 py-3 bg-accent-purple text-white rounded-xl font-semibold hover:bg-accent-purple/90 transition-colors text-center">
                            <span class="material-icons-round text-sm mr-2">play_arrow</span>
                            Continue Quiz
                        </a>
                    </div>
                @elseif($canAttempt)
                    <div class="bg-white rounded-xl p-6 border border-neutral-200">
                        <h3 class="text-lg font-bold text-neutral-900 mb-4">Start Quiz</h3>
                        <p class="text-neutral-600 mb-6">Ready to test your knowledge? This quiz has {{ $quiz->number_of_questions }} questions.</p>
                        <a href="{{ route('zenithalms.quiz.start', $quiz->id) }}" 
                           class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                            <span class="material-icons-round text-sm mr-2">play_arrow</span>
                            Start Quiz
                        </a>
                    </div>
                @else
                    <div class="bg-white rounded-xl p-6 border border-neutral-200">
                        <h3 class="text-lg font-bold text-neutral-900 mb-4">Max Attempts Reached</h3>
                        <p class="text-neutral-600 mb-6">You have reached the maximum number of attempts for this quiz.</p>
                        <button disabled 
                                class="w-full px-6 py-3 bg-neutral-300 text-neutral-500 rounded-xl font-semibold cursor-not-allowed text-center">
                            <span class="material-icons-round text-sm mr-2">block</span>
                            Max Attempts Reached
                        </button>
                    </div>
                @endif
            @else
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900 mb-4">Start Quiz</h3>
                    <p class="text-neutral-600 mb-6">Login to start this quiz and track your progress.</p>
                    <a href="{{ route('login') }}" 
                       class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">login</span>
                        Login to Start
                    </a>
                </div>
            @endif

            <!-- Quiz Info -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Quiz Information</h3>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Difficulty</span>
                        <span class="font-semibold">{{ ucfirst($quiz->difficulty_level) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Questions</span>
                        <span class="font-semibold">{{ $quiz->number_of_questions }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Time Limit</span>
                        <span class="font-semibold">{{ $quiz->time_limit_minutes ?? 'Unlimited' }} minutes</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Passing Score</span>
                        <span class="font-semibold">{{ $quiz->passing_score }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Max Attempts</span>
                        <span class="font-semibold">{{ $quiz->max_attempts }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Shuffle Questions</span>
                        <span class="font-semibold">{{ $quiz->shuffle_questions ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Show Answers</span>
                        <span class="font-semibold">{{ $quiz->show_answers ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <!-- Instructor Info -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Created By</h3>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="material-icons-round text-primary-600">person</span>
                    </div>
                    <div>
                        <div class="font-semibold">{{ $quiz->createdBy->name }}</div>
                        <div class="text-sm text-neutral-600">Quiz Creator</div>
                    </div>
                </div>
            </div>

            <!-- Related Quizzes -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Related Quizzes</h3>
                <div class="space-y-3">
                    <!-- Related quizzes would be loaded here -->
                    <p class="text-neutral-600 text-sm">Related quizzes will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
