@extends('zenithalms.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">AI Learning Assistant</h1>
        <p class="text-gray-600">Your intelligent companion for personalized learning</p>
    </div>

    <!-- Chat Interface -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-500 text-white p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="text-2xl">🤖</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Zenitha AI Assistant</h2>
                    <p class="text-blue-100 text-sm">Always here to help you learn</p>
                </div>
                <div class="ml-auto">
                    <span class="px-3 py-1 bg-green-400 text-green-900 text-sm font-semibold rounded-full">Online</span>
                </div>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="h-96 overflow-y-auto p-6 bg-gray-50">
            <!-- AI Welcome Message -->
            <div class="flex gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-blue-600">🤖</span>
                </div>
                <div class="bg-white rounded-lg p-4 max-w-md">
                    <p class="text-gray-800">Hello! I'm your AI learning assistant. I can help you with:</p>
                    <ul class="list-disc list-inside mt-2 text-gray-600">
                        <li>Answering questions about your courses</li>
                        <li>Providing personalized learning recommendations</li>
                        <li>Helping with difficult concepts</li>
                        <li>Suggesting study strategies</li>
                    </ul>
                    <p class="mt-2 text-gray-800">How can I help you today?</p>
                </div>
            </div>

            <!-- User Message -->
            <div class="flex gap-3 mb-4 justify-end">
                <div class="bg-blue-600 text-white rounded-lg p-4 max-w-md">
                    <p>I'm struggling with JavaScript arrays. Can you help me understand them better?</p>
                </div>
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-gray-600">👤</span>
                </div>
            </div>

            <!-- AI Response -->
            <div class="flex gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-blue-600">🤖</span>
                </div>
                <div class="bg-white rounded-lg p-4 max-w-md">
                    <p class="text-gray-800 mb-3">I'd be happy to help you understand JavaScript arrays! Let me break it down for you:</p>
                    
                    <div class="bg-gray-100 rounded p-3 mb-3">
                        <code class="text-sm">
                            // Creating an array<br>
                            const fruits = ['apple', 'banana', 'orange'];<br><br>
                            // Accessing elements<br>
                            console.log(fruits[0]); // 'apple'<br><br>
                            // Adding elements<br>
                            fruits.push('grape');<br><br>
                            // Array methods<br>
                            fruits.forEach(fruit => console.log(fruit));
                        </code>
                    </div>
                    
                    <p class="text-gray-800">Think of arrays as containers that hold multiple values in order. Each item has an index (position) starting from 0.</p>
                    
                    <p class="text-gray-800 mt-3">Would you like me to explain any specific array methods or show you more examples?</p>
                </div>
            </div>

            <!-- User Message -->
            <div class="flex gap-3 mb-4 justify-end">
                <div class="bg-blue-600 text-white rounded-lg p-4 max-w-md">
                    <p>Can you recommend some learning resources for web development?</p>
                </div>
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-gray-600">👤</span>
                </div>
            </div>

            <!-- AI Response -->
            <div class="flex gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-blue-600">🤖</span>
                </div>
                <div class="bg-white rounded-lg p-4 max-w-md">
                    <p class="text-gray-800 mb-3">Based on your learning progress, here are my recommendations:</p>
                    
                    <div class="space-y-3">
                        <div class="border-l-4 border-blue-500 pl-3">
                            <h4 class="font-semibold">📚 Complete Web Development Bootcamp</h4>
                            <p class="text-gray-600 text-sm">Perfect for your current level - 75% complete</p>
                        </div>
                        
                        <div class="border-l-4 border-green-500 pl-3">
                            <h4 class="font-semibold">📖 JavaScript: The Good Parts</h4>
                            <p class="text-gray-600 text-sm">E-book to deepen your JS understanding</p>
                        </div>
                        
                        <div class="border-l-4 border-purple-500 pl-3">
                            <h4 class="font-semibold">🎯 Daily Coding Challenges</h4>
                            <p class="text-gray-600 text-sm">Practice problems to improve your skills</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-800 mt-3">I'll also create a personalized study plan for you. Would you like me to set that up?</p>
                </div>
            </div>
        </div>

        <!-- Chat Input -->
        <div class="border-t p-4 bg-white">
            <div class="flex gap-3">
                <input type="text" 
                       placeholder="Ask me anything about your learning..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Send
                </button>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex gap-2 mt-3">
                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition">
                    Explain concept
                </button>
                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition">
                    Recommend resources
                </button>
                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition">
                    Create study plan
                </button>
                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition">
                    Practice problems
                </button>
            </div>
        </div>
    </div>

    <!-- AI Features -->
    <div class="mt-8 grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                <span class="text-blue-600 text-xl">🎯</span>
            </div>
            <h3 class="font-semibold mb-2">Personalized Learning</h3>
            <p class="text-gray-600 text-sm">AI adapts to your learning style and pace</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <span class="text-green-600 text-xl">📊</span>
            </div>
            <h3 class="font-semibold mb-2">Progress Tracking</h3>
            <p class="text-gray-600 text-sm">Monitor your learning progress and achievements</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                <span class="text-purple-600 text-xl">🚀</span>
            </div>
            <h3 class="font-semibold mb-2">24/7 Availability</h3>
            <p class="text-gray-600 text-sm">Get help whenever you need it, day or night</p>
        </div>
    </div>
</div>
@endsection
