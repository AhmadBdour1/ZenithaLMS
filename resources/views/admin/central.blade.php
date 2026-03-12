@extends('zenithalms.layouts.app')

@section('title', 'Admin Dashboard - ZenithaLMS')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-neutral-900 mb-2">Admin Dashboard</h1>
            <p class="text-neutral-600">Welcome to your admin control panel, {{ $user->name }}.</p>
        </div>

        <!-- Admin Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-neutral-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-blue-600">people</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">User Management</h3>
                        <p class="text-sm text-neutral-600">Manage system users</p>
                    </div>
                </div>
                <div class="text-sm text-neutral-600">
                    <p>Total users: {{ \App\Models\User::count() }}</p>
                    <p>Active today: {{ \App\Models\User::whereDate('last_login_at', today())->count() }}</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-neutral-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-green-600">school</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">Courses</h3>
                        <p class="text-sm text-neutral-600">Course management</p>
                    </div>
                </div>
                <div class="text-sm text-neutral-600">
                    <p>Total courses: {{ \App\Models\Course::count() }}</p>
                    <p>Published: {{ \App\Models\Course::where('is_published', true)->count() }}</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-neutral-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="material-icons-round text-purple-600">analytics</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-900">Analytics</h3>
                        <p class="text-sm text-neutral-600">System statistics</p>
                    </div>
                </div>
                <div class="text-sm text-neutral-600">
                    <p>System uptime: 99.9%</p>
                    <p>Server load: Normal</p>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white p-6 rounded-xl border border-neutral-200">
            <h2 class="text-xl font-semibold text-neutral-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="/courses" class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <span class="material-icons-round text-neutral-600">visibility</span>
                    <span class="text-sm font-medium text-neutral-900">View Courses</span>
                </a>
                <a href="/ebooks" class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <span class="material-icons-round text-neutral-600">menu_book</span>
                    <span class="text-sm font-medium text-neutral-900">View Ebooks</span>
                </a>
                <a href="/blog" class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <span class="material-icons-round text-neutral-600">article</span>
                    <span class="text-sm font-medium text-neutral-900">View Blog</span>
                </a>
                <a href="/ai/assistant" class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <span class="material-icons-round text-neutral-600">smart_toy</span>
                    <span class="text-sm font-medium text-neutral-900">AI Assistant</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
