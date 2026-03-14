@extends('layouts.central')

@section('title', 'Dashboard - ZenithaLMS')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-neutral-900 mb-2">Welcome back, {{ $user->name }}!</h1>
            <p class="text-neutral-600">Here's what's happening with your learning journey.</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="/courses" class="bg-white p-6 rounded-xl border border-neutral-200 hover:border-primary-300 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-primary-600">school</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">Browse Courses</h3>
                        <p class="text-sm text-neutral-600">Explore available courses</p>
                    </div>
                </div>
            </a>

            <a href="/ebooks" class="bg-white p-6 rounded-xl border border-neutral-200 hover:border-green-300 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-green-600">menu_book</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">Ebooks Library</h3>
                        <p class="text-sm text-neutral-600">Read and download ebooks</p>
                    </div>
                </div>
            </a>

            <a href="/blog" class="bg-white p-6 rounded-xl border border-neutral-200 hover:border-purple-300 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-purple-600">article</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">Blog</h3>
                        <p class="text-sm text-neutral-600">Latest articles and news</p>
                    </div>
                </div>
            </a>

            <a href="/ai/assistant" class="bg-white p-6 rounded-xl border border-neutral-200 hover:border-blue-300 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-blue-600">smart_toy</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">AI Assistant</h3>
                        <p class="text-sm text-neutral-600">Get AI-powered help</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- User Info -->
        <div class="bg-white p-6 rounded-xl border border-neutral-200">
            <h2 class="text-xl font-semibold text-neutral-900 mb-4">Your Account</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-neutral-600">Email</p>
                    <p class="font-medium text-neutral-900">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-neutral-600">Member Since</p>
                    <p class="font-medium text-neutral-900">{{ $user->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
