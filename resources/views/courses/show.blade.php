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
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">
                            {{ $course->category->name }}
                        </span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">
                            {{ ucfirst($course->level) }}
                        </span>
                        @if($course->is_featured)
                            <span class="px-3 py-1 bg-accent-yellow text-white rounded-full text-sm font-bold">
                                FEATURED
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ $course->title }}</h1>
                    <p class="text-xl text-primary-100 mb-6">{{ $course->description }}</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->enrollments->count() }}</div>
                        <div class="text-sm text-primary-200">Students</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">0.0</div>
                        <div class="text-sm text-primary-200">Rating</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->duration_minutes / 60 }}h</div>
                        <div class="text-sm text-primary-200">Duration</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $course->lessons->count() }}</div>
                        <div class="text-sm text-primary-200">Lessons</div>
                    </div>
                </div>

                <!-- Instructor -->
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                            <span class="material-icons-round text-2xl">person</span>
                        </div>
                        <div>
                            <div class="font-semibold text-lg">{{ $course->instructor->name }}</div>
                            <div class="text-primary-200">Expert Instructor</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Card -->
            <div>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Course Image -->
                    <div class="relative h-48">
                        @if($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" 
                                 alt="{{ $course->title }}" 
                                 class="w-full h-full object-cover">
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
                    <div class="p-6">
                        <div class="text-center mb-6">
                            @if($course->is_free)
                                <div class="text-3xl font-bold text-accent-green mb-2">Free</div>
                            @else
                                <div class="text-3xl font-bold text-neutral-900 mb-2">
                                    ${{ number_format($course->price, 2) }}
                                </div>
                            @endif
                            <div class="text-sm text-neutral-500">
                                {{ $course->enrollments->count() }} students enrolled
                            </div>
                        </div>

                        <button class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors mb-3">
                            Enroll Now
                        </button>

                        <!-- Course Features -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">{{ $course->duration_minutes / 60 }} hours of content</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">{{ $course->lessons->count() }} lessons</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Certificate of completion</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Lifetime access</span>
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
        <div class="lg:col-span-2 space-y-8">
            <!-- What You'll Learn -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">What You'll Learn</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="prose max-w-none">
                        @if($course->what_you_will_learn)
                            @if(is_array($course->what_you_will_learn))
                                <ul class="space-y-2">
                                    @foreach($course->what_you_will_learn as $item)
                                        <li class="flex items-start gap-2">
                                            <span class="material-icons-round text-primary-600 text-sm mt-1">check</span>
                                            <span>{{ $item }}</span>
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
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Course Content</h2>
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    @if($course->lessons->count() > 0)
                        @foreach($course->lessons as $index => $lesson)
                            <div class="border-b border-neutral-200 last:border-b-0">
                                <div class="p-4 hover:bg-neutral-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-sm font-semibold text-primary-600">
                                                {{ $index + 1 }}
                                            </div>
                                            <div>
                                                <div class="font-semibold">{{ $lesson->title }}</div>
                                                <div class="text-sm text-neutral-600">{{ $lesson->duration ?? 'N/A' }} minutes</div>
                                            </div>
                                        </div>
                                        <span class="material-icons-round text-neutral-400">play_arrow</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-8 text-center">
                            <span class="material-icons-round text-4xl text-neutral-300 mb-4">video_library</span>
                            <p class="text-neutral-600">Course content will be available soon.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Requirements -->
            @if($course->requirements)
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Requirements</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="prose max-w-none">
                        @if(is_array($course->requirements))
                            <ul class="space-y-2">
                                @foreach($course->requirements as $requirement)
                                    <li class="flex items-start gap-2">
                                        <span class="material-icons-round text-primary-600 text-sm mt-1">check</span>
                                        <span>{{ $requirement }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            {!! nl2br(e($course->requirements)) !!}
                        @endif
                    </div>
                </div>
            </section>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Instructor Info -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Your Instructor</h3>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="material-icons-round text-2xl text-primary-600">person</span>
                    </div>
                    <div>
                        <div class="font-semibold">{{ $course->instructor->name }}</div>
                        <div class="text-sm text-neutral-600">Expert Instructor</div>
                    </div>
                </div>
                <p class="text-neutral-600 text-sm">
                    Experienced instructor with expertise in {{ $course->category->name }} 
                    and passion for teaching.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
