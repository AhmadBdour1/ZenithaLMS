@extends('zenithalms.layouts.app')

@section('title', 'Courses - ZenithaLMS')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Explore Courses</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                Discover our comprehensive collection of courses taught by industry experts
            </p>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white border-b sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <form method="GET" action="{{ route('courses.index') }}" class="space-y-4">
            <!-- Mobile: Stack vertically -->
            <div class="flex flex-col space-y-3 lg:hidden">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request()->get('search') }}"
                           placeholder="Search courses..."
                           class="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <span class="material-icons-round absolute left-3 top-2.5 text-neutral-400">search</span>
                </div>

                <!-- Filter Dropdowns -->
                <div class="grid grid-cols-2 gap-2">
                    <select name="category" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500 text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" {{ request()->get('category') == $category->slug ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="level" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500 text-sm">
                        <option value="">All Levels</option>
                        <option value="beginner" {{ request()->get('level') == 'beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ request()->get('level') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ request()->get('level') == 'advanced' ? 'selected' : '' }}>Advanced</option>
                        <option value="expert" {{ request()->get('level') == 'expert' ? 'selected' : '' }}>Expert</option>
                    </select>

                    <select name="price_type" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500 text-sm">
                        <option value="">All Prices</option>
                        <option value="free" {{ request()->get('price_type') == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="paid" {{ request()->get('price_type') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>

                    <select name="sort" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500 text-sm">
                        <option value="">Sort By</option>
                        <option value="newest" {{ request()->get('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="price_asc" {{ request()->get('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request()->get('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="featured" {{ request()->get('sort') == 'featured' ? 'selected' : '' }}>Featured</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                        Apply Filters
                    </button>
                    <a href="{{ route('courses.index') }}"
                       class="flex-1 px-6 py-2 border border-neutral-300 text-neutral-700 rounded-xl font-semibold hover:bg-neutral-50 transition-colors text-center">
                        Clear
                    </a>
                </div>
            </div>

            <!-- Desktop: Multi-row layout -->
            <div class="hidden lg:flex lg:flex-col lg:space-y-4">
                <!-- First Row: Search + Main Filters -->
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-64">
                        <div class="relative">
                            <input type="text"
                                   name="search"
                                   value="{{ request()->get('search') }}"
                                   placeholder="Search courses..."
                                   class="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                            <span class="material-icons-round absolute left-3 top-2.5 text-neutral-400">search</span>
                        </div>
                    </div>

                    <!-- Category & Level -->
                    <select name="category" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" {{ request()->get('category') == $category->slug ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="level" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                        <option value="">All Levels</option>
                        <option value="beginner" {{ request()->get('level') == 'beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ request()->get('level') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ request()->get('level') == 'advanced' ? 'selected' : '' }}>Advanced</option>
                        <option value="expert" {{ request()->get('level') == 'expert' ? 'selected' : '' }}>Expert</option>
                    </select>
                </div>

                <!-- Second Row: Price + Sort + Actions -->
                <div class="flex flex-wrap items-center gap-4">
                    <select name="price_type" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                        <option value="">All Prices</option>
                        <option value="free" {{ request()->get('price_type') == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="paid" {{ request()->get('price_type') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>

                    <select name="sort" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                        <option value="">Sort By</option>
                        <option value="newest" {{ request()->get('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="price_asc" {{ request()->get('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request()->get('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="featured" {{ request()->get('sort') == 'featured' ? 'selected' : '' }}>Featured</option>
                    </select>

                    <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                        Apply Filters
                    </button>

                    <a href="{{ route('courses.index') }}"
                       class="px-6 py-2 border border-neutral-300 text-neutral-700 rounded-xl font-semibold hover:bg-neutral-50 transition-colors">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Courses Grid -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12">
    @if($courses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($courses as $course)
                <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Course Image -->
                    <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                        <img src="{{ $course->thumbnail_url }}"
                             alt="{{ $course->title }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">

                        <!-- Badges -->
                        <div class="absolute top-3 left-3 flex gap-2">
                            @if($course->is_featured ?? false)
                                <span class="px-2 py-1 bg-accent-yellow text-white text-xs font-bold rounded-full">
                                    FEATURED
                                </span>
                            @endif
                            @if($course->is_free)
                                <span class="px-2 py-1 bg-accent-green text-white text-xs font-bold rounded-full">
                                    FREE
                                </span>
                            @endif
                        </div>

                        <!-- Price -->
                        <div class="absolute bottom-3 right-3">
                            @if($course->is_free)
                                <span class="px-3 py-1 bg-accent-green text-white text-sm font-bold rounded-full">
                                    Free
                                </span>
                            @else
                                <span class="px-3 py-1 bg-primary-500 text-white text-sm font-bold rounded-full">
                                    ${{ number_format((float)$course->price, 2) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Course Content -->
                    <div class="p-6">
                        <!-- Category and Level -->
                        <div class="flex items-center gap-2 mb-3">
                            @if($course->category)
                                <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                    {{ $course->category->name }}
                                </span>
                            @endif
                            @if($course->level)
                                <span class="px-2 py-1 bg-neutral-100 text-neutral-600 text-xs font-semibold rounded-full">
                                    {{ ucfirst($course->level) }}
                                </span>
                            @endif
                        </div>

                        <!-- Title -->
                        <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                            {{ $course->title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-neutral-600 text-sm mb-4 line-clamp-3">
                            {{ \Illuminate\Support\Str::limit(strip_tags($course->description), 100) }}
                        </p>

                        <!-- Instructor -->
                        @if($course->instructor)
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span class="material-icons-round text-primary-600 text-sm">person</span>
                                </div>
                                <span class="text-sm text-neutral-600">{{ $course->instructor->name }}</span>
                            </div>
                        @endif

                        <!-- Stats -->
                        <div class="flex items-center justify-between text-sm text-neutral-500 mb-4">
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">schedule</span>
                                {{ $course->duration_minutes ? round($course->duration_minutes / 60) : 0 }}h
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">people</span>
                                {{ $course->enrollments_count ?? 0 }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">star</span>
                                New
                            </span>
                        </div>

                        <!-- Action Button -->
                        <a href="{{ route('courses.show', $course->slug) }}"
                           class="w-full px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                            View Course
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $courses->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">search_off</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No courses found</h3>
            <p class="text-neutral-600 mb-6">Try adjusting your search criteria or browse all courses</p>
            <a href="{{ route('courses.index') }}"
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse All Courses
            </a>
        </div>
    @endif
</div>
@endsection
