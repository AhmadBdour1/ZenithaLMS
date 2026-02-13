@extends('zenithalms.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Instructor Dashboard</h1>
        <p class="text-gray-600">Manage your courses and track student progress</p>
    </div>

    <!-- Stats Overview -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 text-sm">Total Courses</h3>
                <span class="text-blue-500 text-2xl">📚</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">12</p>
            <p class="text-green-500 text-sm">+2 this month</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 text-sm">Total Students</h3>
                <span class="text-green-500 text-2xl">👥</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">1,234</p>
            <p class="text-green-500 text-sm">+156 this month</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 text-sm">Revenue</h3>
                <span class="text-purple-500 text-2xl">💰</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">$45,678</p>
            <p class="text-green-500 text-sm">+12% this month</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 text-sm">Avg. Rating</h3>
                <span class="text-yellow-500 text-2xl">⭐</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">4.8</p>
            <p class="text-gray-500 text-sm">From 892 reviews</p>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Your Courses</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 border rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="text-blue-600 text-2xl">📚</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Complete Web Development Bootcamp</h3>
                        <p class="text-gray-600 text-sm">342 students • $89.99</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 bg-green-100 text-green-600 text-xs font-semibold rounded-full">Published</span>
                    <button class="text-blue-600 hover:text-blue-700">Edit</button>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 border rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="text-purple-600 text-2xl">🎨</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">UI/UX Design Fundamentals</h3>
                        <p class="text-gray-600 text-sm">128 students • $69.99</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-600 text-xs font-semibold rounded-full">Draft</span>
                    <button class="text-blue-600 hover:text-blue-700">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Create New Course</button>
                <button class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">View Analytics</button>
                <button class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Manage Students</button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h2>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="text-green-500">✓</span>
                    <p class="text-sm">New student enrolled in Web Development</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-blue-500">⭐</span>
                    <p class="text-sm">5-star review received for UI/UX course</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-purple-500">💬</span>
                    <p class="text-sm">3 new questions in course forums</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
