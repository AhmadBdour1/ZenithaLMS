@extends('zenithalms.layouts.app')

@section('title', 'Course Not Found - ZenithaLMS')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-neutral-50">
    <div class="max-w-md w-full text-center px-4">
        <div class="mb-8">
            <div class="w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-4xl text-primary-600">school</span>
            </div>
            <h1 class="text-6xl font-bold text-neutral-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-neutral-800 mb-4">Course Not Found</h2>
            <p class="text-neutral-600 mb-8">
                The course you're looking for doesn't exist or has been removed. 
                Browse our available courses below.
            </p>
        </div>

        <div class="space-y-4">
            <a href="{{ route('courses.index') }}" 
               class="inline-block px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse All Courses
            </a>
            
            <div class="text-sm text-neutral-500">
                Or <a href="{{ route('login') }}" class="text-primary-600 hover:underline">login</a> to your account
            </div>
        </div>

        <!-- Popular Courses -->
        @if(isset($popularCourses) && $popularCourses->count() > 0)
        <div class="mt-12">
            <h3 class="text-lg font-semibold text-neutral-800 mb-4">Popular Courses</h3>
            <div class="space-y-3">
                @foreach($popularCourses->take(3) as $course)
                <a href="{{ route('courses.show', $course->slug) }}" 
                   class="block p-4 bg-white rounded-lg border border-neutral-200 hover:border-primary-300 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                            <span class="material-icons-round text-primary-600">school</span>
                        </div>
                        <div class="text-left">
                            <div class="font-semibold text-neutral-800">{{ $course->title }}</div>
                            <div class="text-sm text-neutral-600">
                                @if($course->is_free)
                                    Free
                                @else
                                    ${{ number_format($course->price, 2) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
