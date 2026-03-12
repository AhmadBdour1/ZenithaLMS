@extends('zenithalms.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Student Dashboard</h1>
        <p class="text-gray-600">Track your learning progress and achievements</p>
    </div>

    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-4">Welcome back, Student!</h2>
        <p class="mb-6">You're making great progress! Keep up the excellent work.</p>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white/20 rounded-lg p-4">
                <p class="text-3xl font-bold mb-2">5</p>
                <p class="text-sm">Courses Enrolled</p>
            </div>
            <div class="bg-white/20 rounded-lg p-4">
                <p class="text-3xl font-bold mb-2">68%</p>
                <p class="text-sm">Avg. Progress</p>
            </div>
            <div class="bg-white/20 rounded-lg p-4">
                <p class="text-3xl font-bold mb-2">12</p>
                <p class="text-sm">Certificates Earned</p>
            </div>
        </div>
    </div>

    <!-- Current Courses -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Continue Learning</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">Complete Web Development Bootcamp</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Progress</span>
                        <span>75%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
                <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Continue</button>
            </div>

            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-green-500 to-teal-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">Digital Marketing Masterclass</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Progress</span>
                        <span>45%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Continue</button>
            </div>

            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">UI/UX Design Fundamentals</h3>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Progress</span>
                        <span>90%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: 90%"></div>
                    </div>
                </div>
                <button class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">Continue</button>
            </div>
        </div>
    </div>

    <!-- Achievements -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Achievements</h2>
        <div class="grid md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <span class="text-2xl">🏆</span>
                </div>
                <p class="font-semibold">Fast Learner</p>
                <p class="text-gray-600 text-sm">Complete 5 courses</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <span class="text-2xl">🎯</span>
                </div>
                <p class="font-semibold">Goal Setter</p>
                <p class="text-gray-600 text-sm">Set learning goals</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <span class="text-2xl">📚</span>
                </div>
                <p class="font-semibold">Bookworm</p>
                <p class="text-gray-600 text-sm">Read 10 e-books</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <span class="text-2xl">⭐</span>
                </div>
                <p class="font-semibold">Top Student</p>
                <p class="text-gray-600 text-sm">90% avg score</p>
            </div>
        </div>
    </div>

    <!-- Recommended Courses -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recommended for You</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">Machine Learning Basics</h3>
                <p class="text-gray-600 text-sm mb-4">Introduction to AI and ML concepts</p>
                <button class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">View Course</button>
            </div>

            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">Mobile App Development</h3>
                <p class="text-gray-600 text-sm mb-4">Build iOS and Android apps</p>
                <button class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">View Course</button>
            </div>

            <div class="border rounded-lg p-4">
                <div class="h-32 bg-gradient-to-r from-teal-500 to-green-500 rounded-lg mb-4"></div>
                <h3 class="font-semibold mb-2">Business Strategy</h3>
                <p class="text-gray-600 text-sm mb-4">Strategic planning for businesses</p>
                <button class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">View Course</button>
            </div>
        </div>
    </div>
</div>
@endsection
