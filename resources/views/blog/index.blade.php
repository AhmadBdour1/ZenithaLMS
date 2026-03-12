@extends('zenithalms.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Blog</h1>
        <p class="text-gray-600">Latest articles, tutorials, and insights from our experts</p>
    </div>

    <!-- Featured Article -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-12">
        <div class="h-64 bg-gradient-to-r from-blue-500 to-purple-500"></div>
        <div class="p-8">
            <div class="flex items-center gap-4 mb-4">
                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-sm font-semibold rounded-full">Featured</span>
                <span class="text-gray-500 text-sm">5 min read</span>
            </div>
            <h2 class="text-3xl font-bold mb-4">The Future of Online Learning: Trends to Watch in 2024</h2>
            <p class="text-gray-600 mb-6">Discover the latest trends shaping the future of education and how AI is revolutionizing the way we learn...</p>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                    <div>
                        <p class="font-semibold">John Anderson</p>
                        <p class="text-gray-500 text-sm">March 15, 2024</p>
                    </div>
                </div>
                <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Read More</button>
            </div>
        </div>
    </div>

    <!-- All Articles -->
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Recent Articles</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Article Card 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="h-48 bg-gradient-to-r from-green-500 to-teal-500"></div>
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-1 bg-green-100 text-green-600 text-xs font-semibold rounded-full">Tutorial</span>
                        <span class="text-gray-500 text-xs">3 min read</span>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Getting Started with Web Development</h3>
                    <p class="text-gray-600 text-sm mb-4">A comprehensive guide for beginners looking to start their web development journey...</p>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 text-sm">March 14, 2024</span>
                        <button class="text-blue-600 hover:text-blue-700 font-semibold">Read More →</button>
                    </div>
                </div>
            </div>

            <!-- Article Card 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="h-48 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-1 bg-purple-100 text-purple-600 text-xs font-semibold rounded-full">Design</span>
                        <span class="text-gray-500 text-xs">5 min read</span>
                    </div>
                    <h3 class="font-bold text-lg mb-2">UI/UX Best Practices for 2024</h3>
                    <p class="text-gray-600 text-sm mb-4">Latest design trends and user experience principles every designer should know...</p>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 text-sm">March 13, 2024</span>
                        <button class="text-blue-600 hover:text-blue-700 font-semibold">Read More →</button>
                    </div>
                </div>
            </div>

            <!-- Article Card 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="h-48 bg-gradient-to-r from-orange-500 to-red-500"></div>
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-1 bg-orange-100 text-orange-600 text-xs font-semibold rounded-full">Business</span>
                        <span class="text-gray-500 text-xs">4 min read</span>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Digital Marketing Strategies That Work</h3>
                    <p class="text-gray-600 text-sm mb-4">Proven strategies to grow your online presence and reach more customers...</p>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 text-sm">March 12, 2024</span>
                        <button class="text-blue-600 hover:text-blue-700 font-semibold">Read More →</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
