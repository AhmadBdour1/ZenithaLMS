@extends('zenithalms.layouts.app')

@section('title', $course->title . ' - ZenithaLMS')

@section('content')
<!-- Course Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Course Info -->
            <div class="lg:col-span-2">
                <div class="mb-4">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @if($course->category)
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">
                                {{ $course->category->name }}
                            </span>
                        @endif
                        @if($course->level)
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">
                                {{ ucfirst($course->level) }}
                            </span>
                        @endif
                        @if($course->is_featured)
                            <span class="px-3 py-1 bg-accent-yellow text-white rounded-full text-sm font-bold">
                                FEATURED
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-4 break-words">{{ $course->title }}</h1>
                    <p class="text-xl text-primary-100 mb-6 break-words">{{ $course->description }}</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->enrollments_count ?? 0 }}</div>
                        <div class="text-sm text-primary-200">Students</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">0.0</div>
                        <div class="text-sm text-primary-200">Rating</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->duration_minutes ? round($course->duration_minutes / 60) : 0 }}h</div>
                        <div class="text-sm text-primary-200">Duration</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->lessons_count ?? 0 }}</div>
                        <div class="text-sm text-primary-200">Lessons</div>
                    </div>
                </div>

                <!-- Instructor -->
                @if($course->instructor)
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-round text-2xl">person</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-lg break-words">{{ $course->instructor->name }}</div>
                            <div class="text-primary-200">Expert Instructor</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Course Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-full">
                    <!-- Course Image -->
                    <div class="relative h-48 w-full">
                        @if($course->thumbnail)
                            <img src="{{ $course->thumbnail_url ?? asset('images/course-placeholder.png') }}" 
                                 alt="{{ $course->title }}" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='{{ asset('images/course-placeholder.png') }}'">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-primary-100 to-accent-purple/20 flex items-center justify-center">
                                <span class="material-icons-round text-4xl text-primary-300">school</span>
                            </div>
                        @endif
                        
                        @if($course->preview_video)
                            <button class="absolute inset-0 flex items-center justify-center bg-black/50 hover:bg-black/60 transition-colors">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                    <span class="material-icons-round text-primary-600 text-2xl">play_arrow</span>
                                </div>
                            </button>
                        @endif
                    </div>

                    <!-- Price and Enrollment -->
                    <div class="p-6 w-full">
                        <div class="text-center mb-6">
                            @if($course->is_free)
                                <div class="text-3xl font-bold text-accent-green mb-2">Free</div>
                            @else
                                <div class="text-3xl font-bold text-neutral-900 mb-2 break-words">
                                    ${{ number_format($course->price, 2) }}
                                </div>
                            @endif
                            <div class="text-sm text-neutral-500 break-words">
                                {{ $course->enrollments_count ?? 0 }} students enrolled
                            </div>
                        </div>

                        @if(auth()->check())
                            @if($isEnrolled)
                                <button class="w-full px-6 py-3 bg-accent-green text-white rounded-xl font-semibold hover:bg-accent-green/90 transition-colors mb-3">
                                    Continue Learning
                                </button>
                            @else
                                <button class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors mb-3">
                                    Enroll Now
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="block w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors mb-3 text-center break-words">
                                Login to Enroll
                            </a>
                        @endif

                        <!-- Course Features -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600 flex-shrink-0">check_circle</span>
                                <span class="text-sm break-words">{{ $course->duration_minutes ? round($course->duration_minutes / 60) : 0 }} hours of content</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600 flex-shrink-0">check_circle</span>
                                <span class="text-sm break-words">{{ $course->lessons_count ?? 0 }} lessons</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600 flex-shrink-0">check_circle</span>
                                <span class="text-sm break-words">Certificate of completion</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600 flex-shrink-0">check_circle</span>
                                <span class="text-sm break-words">Lifetime access</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8 w-full">
            <!-- What You'll Learn -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4 break-words">What You'll Learn</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200 w-full">
                    <div class="prose max-w-none">
                        @if($course->what_you_will_learn)
                            @if(is_array($course->what_you_will_learn))
                                <ul class="space-y-2">
                                    @foreach($course->what_you_will_learn as $item)
                                        <li class="flex items-start gap-2">
                                            <span class="material-icons-round text-primary-600 text-sm mt-1 flex-shrink-0">check</span>
                                            <span class="break-words">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                {!! nl2br(e($course->what_you_will_learn)) !!}
                            @endif
                        @else
                            <p class="text-neutral-600">Course learning outcomes will be available soon.</p>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Course Content -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4 break-words">Course Content</h2>
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden w-full">
                    @if($lessons->count() > 0)
                        @foreach($lessons as $index => $lesson)
                            <div class="border-b border-neutral-200 last:border-b-0">
                                <div class="p-4 hover:bg-neutral-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-sm font-semibold text-primary-600 flex-shrink-0">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold break-words">{{ $lesson->title }}</div>
                                                <div class="text-sm text-neutral-600">{{ $lesson->duration_minutes ?? 'N/A' }} minutes</div>
                                            </div>
                                        </div>
                                        <span class="material-icons-round text-neutral-400 flex-shrink-0">play_arrow</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-6 text-center text-neutral-500">
                            <span class="material-icons-round text-4xl mb-2">video_library</span>
                            <p>Course content will be available soon.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Requirements -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4 break-words">Requirements</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200 w-full">
                    <div class="prose max-w-none">
                        @if($course->requirements)
                            @if(is_array($course->requirements))
                                <ul class="space-y-2">
                                    @foreach($course->requirements as $requirement)
                                        <li class="flex items-start gap-2">
                                            <span class="material-icons-round text-primary-600 text-sm mt-1 flex-shrink-0">check_circle</span>
                                            <span class="break-words">{{ $requirement }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                {!! nl2br(e($course->requirements)) !!}
                            @endif
                        @else
                            <p class="text-neutral-600">No specific requirements for this course.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6 w-full">
            <!-- Course Info Card -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200 w-full">
                <h3 class="text-lg font-semibold mb-4 break-words">Course Info</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Duration</span>
                        <span class="font-medium">{{ $course->duration_minutes ? round($course->duration_minutes / 60) : 0 }} hours</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Lessons</span>
                        <span class="font-medium">{{ $course->lessons_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Level</span>
                        <span class="font-medium">{{ ucfirst($course->level ?? 'All') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Students</span>
                        <span class="font-medium">{{ $course->enrollments_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Language</span>
                        <span class="font-medium">English</span>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            @if($course->category || $course->tags)
            <div class="bg-white rounded-xl p-6 border border-neutral-200 w-full">
                <h3 class="text-lg font-semibold mb-4 break-words">Categories & Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @if($course->category)
                        <span class="px-3 py-1 bg-primary-100 text-primary-600 rounded-full text-sm">
                            {{ $course->category->name }}
                        </span>
                    @endif
                    @if($course->tags && is_array($course->tags))
                        @foreach($course->tags as $tag)
                            <span class="px-3 py-1 bg-neutral-100 text-neutral-600 rounded-full text-sm">
                                {{ $tag }}
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>
            @endif

            <!-- Share -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200 w-full">
                <h3 class="text-lg font-semibold mb-4 break-words">Share This Course</h3>
                <div class="flex gap-2">
                    <button class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors text-sm">
                        <span class="material-icons-round text-sm">share</span>
                    </button>
                    <button class="flex-1 px-4 py-2 bg-neutral-100 text-neutral-700 rounded-lg hover:bg-neutral-200 transition-colors text-sm">
                        <span class="material-icons-round text-sm">favorite_border</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
