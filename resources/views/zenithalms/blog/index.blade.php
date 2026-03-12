@extends('zenithalms.layouts.app')

@section('title', 'Blog - ZenithaLMS')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">Our Blog</h1>
                <p class="text-xl text-purple-100">Insights, tutorials, and updates from the ZenithaLMS team</p>
            </div>
        </div>
    </div>

    <!-- Blog Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                @if($blogs->count() > 0)
                    <div class="space-y-8">
                        @foreach($blogs as $blog)
                            <article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                @if($blog->featured_image)
                                    <div class="h-48 bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center">
                                        <img src="{{ $blog->featured_image }}" alt="{{ $blog->title }}" class="h-full w-full object-cover">
                                    </div>
                                @else
                                    <div class="h-48 bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center">
                                        <span class="material-icons-round text-white text-4xl">article</span>
                                    </div>
                                @endif
                                
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-2">
                                        @if($blog->category)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ $blog->category->name }}
                                            </span>
                                        @endif
                                        <span class="text-sm text-gray-500">
                                            {{ $blog->published_at->format('M j, Y') }}
                                        </span>
                                    </div>
                                    
                                    <h2 class="text-2xl font-semibold text-gray-900 mb-2">
                                        <a href="{{ route('zenithalms.blog.show', $blog->slug) }}" class="hover:text-purple-600 transition-colors">
                                            {{ $blog->title }}
                                        </a>
                                    </h2>
                                    <p class="text-gray-600 mb-4 line-clamp-3">{{ $blog->excerpt }}</p>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            @if($blog->user)
                                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-2">
                                                    <span class="text-xs font-medium text-gray-600">
                                                        {{ substr($blog->user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <span class="text-sm text-gray-700">{{ $blog->user->name }}</span>
                                            @else
                                                <span class="text-sm text-gray-500">Author</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <span class="material-icons-round text-sm mr-1">visibility</span>
                                                {{ $blog->views_count ?? 0 }}
                                            </span>
                                            <span class="flex items-center">
                                                <span class="material-icons-round text-sm mr-1">chat_bubble_outline</span>
                                                {{ $blog->comments_count ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $blogs->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <span class="material-icons-round text-gray-400 text-2xl">article</span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No blog posts available</h3>
                        <p class="text-gray-500">Check back later for new articles.</p>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Featured Posts -->
                @if($featuredPosts->count() > 0)
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Posts</h3>
                        <div class="space-y-4">
                            @foreach($featuredPosts as $post)
                                <div class="border-l-4 border-purple-500 pl-4">
                                    <h4 class="font-medium text-gray-900 mb-1">
                                        <a href="{{ route('zenithalms.blog.show', $post->slug) }}" class="hover:text-purple-600 transition-colors text-sm">
                                            {{ $post->title }}
                                        </a>
                                    </h4>
                                    <p class="text-xs text-gray-500">{{ $post->published_at->format('M j, Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Categories -->
                @if($categories->count() > 0)
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Categories</h3>
                        <div class="space-y-2">
                            @foreach($categories as $category)
                                <a href="#" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tags -->
                @if($tags->count() > 0)
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
