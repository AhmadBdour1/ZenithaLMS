@extends('zenithalms.layouts.app')

@section('title', 'Ebooks - ZenithaLMS')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Ebook Marketplace</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                Discover our comprehensive collection of educational ebooks and digital resources
            </p>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-white border-b sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search ebooks..." 
                           class="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <span class="material-icons-round absolute left-3 top-2.5 text-neutral-400">search</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <select name="category" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="price_type" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Prices</option>
                    <option value="free" {{ request('price_type') == 'free' ? 'selected' : '' }}>Free</option>
                    <option value="paid" {{ request('price_type') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>

                <select name="file_type" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Formats</option>
                    <option value="pdf" {{ request('file_type') == 'pdf' ? 'selected' : '' }}>PDF</option>
                    <option value="epub" {{ request('file_type') == 'epub' ? 'selected' : '' }}>EPUB</option>
                    <option value="mobi" {{ request('file_type') == 'mobi' ? 'selected' : '' }}>MOBI</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Featured Ebooks -->
@if($featuredEbooks->count() > 0)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900 mb-2">Featured Ebooks</h2>
        <p class="text-neutral-600">Hand-picked ebooks by our team</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        @foreach($featuredEbooks as $ebook)
            <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                <!-- Ebook Image -->
                <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                    @if($ebook->thumbnail)
                        <img src="{{ $ebook->getThumbnailUrl() }}" 
                             alt="{{ $ebook->title }}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <span class="material-icons-round text-4xl text-primary-300">menu_book</span>
                        </div>
                    @endif
                    
                    <!-- Featured Badge -->
                    <div class="absolute top-3 left-3">
                        <span class="px-3 py-1 bg-accent-yellow text-white text-sm font-bold rounded-full">
                            FEATURED
                        </span>
                    </div>

                    <!-- Price -->
                    <div class="absolute bottom-3 right-3">
                        @if($ebook->is_free)
                            <span class="px-3 py-1 bg-accent-green text-white text-sm font-bold rounded-full">
                                Free
                            </span>
                        @else
                            <span class="px-3 py-1 bg-primary-500 text-white text-sm font-bold rounded-full">
                                ${{ number_format($ebook->price, 2) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Ebook Content -->
                <div class="p-6">
                    <!-- Category -->
                    <div class="mb-3">
                        <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                            {{ $ebook->category->name }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                        {{ $ebook->title }}
                    </h3>

                    <!-- Description -->
                    <p class="text-neutral-600 text-sm mb-4 line-clamp-3">
                        {{ Str::limit(strip_tags($ebook->description), 100) }}
                    </p>

                    <!-- Author -->
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="material-icons-round text-primary-600 text-sm">person</span>
                        </div>
                        <span class="text-sm text-neutral-600">{{ $ebook->user->name }}</span>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center justify-between text-sm text-neutral-500 mb-4">
                        <span class="flex items-center gap-1">
                            <span class="material-icons-round text-sm">download</span>
                            {{ $ebook->download_count }}
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-icons-round text-sm">star</span>
                            {{ number_format($ebook->getAverageRating(), 1) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-icons-round text-sm">description</span>
                            {{ $ebook->getFileSizeFormattedAttribute() }}
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <a href="{{ route('zenithalms.ebooks.show', $ebook->slug) }}" 
                           class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                            View Details
                        </a>
                        @if(auth()->check())
                            <button onclick="toggleFavorite({{ $ebook->id }})" 
                                    class="px-3 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors"
                                    data-ebook-id="{{ $ebook->id }}"
                                    data-is-favorited="{{ $ebook->isFavoritedBy(auth()->id()) ? 'true' : 'false' }}">
                                <span class="material-icons-round text-sm">
                                    {{ $ebook->isFavoritedBy(auth()->id()) ? 'favorite' : 'favorite_border' }}
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- All Ebooks -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900 mb-2">All Ebooks</h2>
        <p class="text-neutral-600">{{ $ebooks->total() }} ebooks available</p>
    </div>

    @if($ebooks->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($ebooks as $ebook)
                <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Ebook Image -->
                    <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                        @if($ebook->thumbnail)
                            <img src="{{ $ebook->getThumbnailUrl() }}" 
                                 alt="{{ $ebook->title }}" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="material-icons-round text-4xl text-primary-300">menu_book</span>
                            </div>
                        @endif
                        
                        <!-- Badges -->
                        <div class="absolute top-3 left-3 flex gap-2">
                            @if($ebook->is_free)
                                <span class="px-2 py-1 bg-accent-green text-white text-xs font-bold rounded-full">
                                    FREE
                                </span>
                            @endif
                        </div>

                        <!-- Price -->
                        <div class="absolute bottom-3 right-3">
                            @if($ebook->is_free)
                                <span class="px-3 py-1 bg-accent-green text-white text-sm font-bold rounded-full">
                                    Free
                                </span>
                            @else
                                <span class="px-3 py-1 bg-primary-500 text-white text-sm font-bold rounded-full">
                                    ${{ number_format($ebook->price, 2) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Ebook Content -->
                    <div class="p-6">
                        <!-- Category -->
                        <div class="mb-3">
                            <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                {{ $ebook->category->name }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                            {{ $ebook->title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-neutral-600 text-sm mb-4 line-clamp-3">
                            {{ Str::limit(strip_tags($ebook->description), 100) }}
                        </p>

                        <!-- Author -->
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="material-icons-round text-primary-600 text-sm">person</span>
                            </div>
                            <span class="text-sm text-neutral-600">{{ $ebook->user->name }}</span>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center justify-between text-sm text-neutral-500 mb-4">
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">download</span>
                                {{ $ebook->download_count }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">star</span>
                                {{ number_format($ebook->getAverageRating(), 1) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-icons-round text-sm">description</span>
                                {{ $ebook->getFileSizeFormattedAttribute() }}
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="{{ route('zenithalms.ebooks.show', $ebook->slug) }}" 
                               class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                                View Details
                            </a>
                            @if(auth()->check())
                                <button onclick="toggleFavorite({{ $ebook->id }})" 
                                        class="px-3 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors"
                                        data-ebook-id="{{ $ebook->id }}"
                                        data-is-favorited="{{ $ebook->isFavoritedBy(auth()->id()) ? 'true' : 'false' }}">
                                    <span class="material-icons-round text-sm">
                                        {{ $ebook->isFavoritedBy(auth()->id()) ? 'favorite' : 'favorite_border' }}
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $ebooks->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">menu_book</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No ebooks found</h3>
            <p class="text-neutral-600 mb-6">Try adjusting your search criteria or browse all ebooks</p>
            <a href="{{ route('zenithalms.ebooks.index') }}" 
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse All Ebooks
            </a>
        </div>
    @endif
</div>

<!-- AI Recommendations Section -->
@if(auth()->check())
<div class="bg-neutral-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">AI Recommendations</h2>
            <p class="text-neutral-600">Personalized ebook recommendations based on your interests</p>
        </div>
        
        <div id="ai-recommendations" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Recommendations will be loaded here -->
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// ZenithaLMS: Toggle favorite functionality
function toggleFavorite(ebookId) {
    const button = document.querySelector(`[data-ebook-id="${ebookId}"]`);
    const icon = button.querySelector('.material-icons-round');
    const isFavorited = button.dataset.isFavorited === 'true';
    
    fetch(`/zenithalms/ebooks/favorites/${ebookId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.dataset.isFavorited = data.is_favorited ? 'true' : 'false';
            icon.textContent = data.is_favorited ? 'favorite' : 'favorite_border';
            
            // Show notification
            showNotification(data.message, data.status === 'added' ? 'success' : 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// ZenithaLMS: Load AI recommendations
function loadRecommendations() {
    fetch('/zenithalms/ebooks/recommendations')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-recommendations');
            if (data.recommendations && data.recommendations.length > 0) {
                container.innerHTML = data.recommendations.map(ebook => `
                    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="relative h-32 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                            ${ebook.thumbnail ? 
                                `<img src="${ebook.getThumbnailUrl()}" alt="${ebook.title}" class="w-full h-full object-cover">` :
                                `<div class="w-full h-full flex items-center justify-center">
                                    <span class="material-icons-round text-3xl text-primary-300">menu_book</span>
                                </div>`
                            }
                            <div class="absolute bottom-2 right-2">
                                <span class="px-2 py-1 bg-primary-500 text-white text-xs font-bold rounded-full">
                                    ${ebook.is_free ? 'Free' : '$' + ebook.price}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2">${ebook.title}</h3>
                            <p class="text-neutral-600 text-xs mb-3 line-clamp-2">${ebook.description}</p>
                            <a href="${route('zenithalms.ebooks.show', ebook.slug)}" 
                               class="w-full px-3 py-2 bg-primary-500 text-white rounded-lg text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                                View Details
                            </a>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-neutral-600 col-span-full">No recommendations available yet</p>';
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
        });
}

// ZenithaLMS: Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'info' ? 'bg-blue-500' : 'bg-gray-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ZenithaLMS: Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load recommendations if user is authenticated
    if (document.getElementById('ai-recommendations')) {
        loadRecommendations();
    }
});
</script>
@endpush
