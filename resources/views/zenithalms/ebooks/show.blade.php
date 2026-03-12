@extends('zenithalms.layouts.app')

@section('title', $ebook->title . ' - ZenithaLMS')

@section('content')
<!-- Ebook Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Ebook Info -->
            <div class="lg:col-span-2">
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">
                            {{ $ebook->category->name }}
                        </span>
                        @if($ebook->is_free)
                            <span class="px-3 py-1 bg-accent-green text-white rounded-full text-sm font-bold">
                                FREE
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ $ebook->title }}</h1>
                    <p class="text-xl text-primary-100 mb-6">{{ $ebook->description }}</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $ebook->download_count }}</div>
                        <div class="text-sm text-primary-200">Downloads</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ number_format($ebook->getAverageRating(), 1) }}</div>
                        <div class="text-sm text-primary-200">Rating</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $ebook->getFileSizeFormattedAttribute() }}</div>
                        <div class="text-sm text-primary-200">Size</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ $ebook->file_type }}</div>
                        <div class="text-sm text-primary-200">Format</div>
                    </div>
                </div>

                <!-- Author -->
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                            <span class="material-icons-round text-2xl">person</span>
                        </div>
                        <div>
                            <div class="font-semibold text-lg">{{ $ebook->user->name }}</div>
                            <div class="text-primary-200">Author</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ebook Card -->
            <div>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Ebook Image -->
                    <div class="relative h-64">
                        @if($ebook->thumbnail)
                            <img src="{{ $ebook->getThumbnailUrl() }}" 
                                 alt="{{ $ebook->title }}" 
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-primary-100 to-accent-purple/20 flex items-center justify-center">
                                <span class="material-icons-round text-4xl text-primary-300">menu_book</span>
                            </div>
                        @endif
                    </div>

                    <!-- Price and Actions -->
                    <div class="p-6">
                        <div class="text-center mb-6">
                            @if($ebook->is_free)
                                <div class="text-3xl font-bold text-accent-green mb-2">Free</div>
                            @else
                                <div class="text-3xl font-bold text-neutral-900 mb-2">
                                    ${{ number_format($ebook->price, 2) }}
                                </div>
                            @endif
                            <div class="text-sm text-neutral-500">
                                {{ $ebook->download_count }} downloads
                            </div>
                        </div>

                        @if(auth()->check())
                            @if($hasAccess)
                                <div class="space-y-3">
                                    <a href="{{ route('zenithalms.ebooks.download', $ebook->id) }}" 
                                       class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center block">
                                        <span class="material-icons-round text-sm mr-2">download</span>
                                        Download Ebook
                                    </a>
                                    
                                    @if($ebook->is_downloadable)
                                        <a href="{{ route('zenithalms.ebooks.read', $ebook->id) }}" 
                                           class="w-full px-6 py-3 bg-accent-purple text-white rounded-xl font-semibold hover:bg-accent-purple/90 transition-colors text-center block">
                                            <span class="material-icons-round text-sm mr-2">menu_book</span>
                                            Read Online
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="space-y-3">
                                    <button onclick="purchaseEbook({{ $ebook->id }})" 
                                            class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                                        <span class="material-icons-round text-sm mr-2">shopping_cart</span>
                                        Purchase Ebook
                                    </button>
                                    
                                    <button onclick="toggleFavorite({{ $ebook->id }})" 
                                            class="w-full px-6 py-3 border border-neutral-300 rounded-xl font-semibold hover:border-primary-500 transition-colors"
                                            data-ebook-id="{{ $ebook->id }}"
                                            data-is-favorited="{{ $isFavorited ? 'true' : 'false' }}">
                                        <span class="material-icons-round text-sm mr-2">
                                            {{ $isFavorited ? 'favorite' : 'favorite_border' }}
                                        </span>
                                        {{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="space-y-3">
                                <a href="{{ route('login') }}" 
                                   class="w-full px-6 py-3 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center block">
                                    <span class="material-icons-round text-sm mr-2">login</span>
                                    Login to Access
                                </a>
                                
                                <a href="{{ route('register') }}" 
                                   class="w-full px-6 py-3 border border-neutral-300 rounded-xl font-semibold hover:border-primary-500 transition-colors text-center block">
                                    <span class="material-icons-round text-sm mr-2">person_add</span>
                                    Create Account
                                </a>
                            </div>
                        @endif

                        <!-- Ebook Features -->
                        <div class="space-y-3 mt-6">
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Instant download</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Lifetime access</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Multiple formats</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="material-icons-round text-primary-600">check_circle</span>
                                <span class="text-sm">Regular updates</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ebook Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Description -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Description</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="prose max-w-none">
                        {!! nl2br(e($ebook->description)) !!}
                    </div>
                </div>
            </section>

            <!-- AI Summary -->
            @if($ebook->ai_summary)
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">AI Summary</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="mb-4">
                        <span class="px-3 py-1 bg-accent-purple text-white text-sm font-semibold rounded-full">
                            AI-Generated Summary
                        </span>
                    </div>
                    <div class="prose max-w-none">
                        <p class="text-neutral-700">{{ $ebook->ai_summary['summary'] ?? 'No summary available' }}</p>
                    </div>
                    
                    @if(isset($ebook->ai_summary['key_topics']))
                    <div class="mt-4">
                        <h4 class="font-semibold text-neutral-900 mb-2">Key Topics:</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($ebook->ai_summary['key_topics'] as $topic)
                                <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                    {{ $topic }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($ebook->ai_summary['sentiment']))
                    <div class="mt-4">
                        <h4 class="font-semibold text-neutral-900 mb-2">Content Sentiment:</h4>
                        <span class="px-2 py-1 bg-{{ 
                            $ebook->ai_summary['sentiment'] === 'positive' ? 'green' : 
                            ($ebook->ai_summary['sentiment'] === 'negative' ? 'red' : 'gray')
                        }}-100 text-white text-xs font-semibold rounded-full">
                            {{ ucfirst($ebook->ai_summary['sentiment']) }}
                        </span>
                    </div>
                    @endif
                </div>
            </section>
            @endif

            <!-- Technical Details -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Technical Details</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">File Format</div>
                            <div class="font-semibold">{{ strtoupper($ebook->file_type) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">File Size</div>
                            <div class="font-semibold">{{ $ebook->getFileSizeFormattedAttribute() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Downloads</div>
                            <div class="font-semibold">{{ $ebook->download_count }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Published</div>
                            <div class="font-semibold">{{ $ebook->created_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Updated</div>
                            <div class="font-semibold">{{ $ebook->updated_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">ISBN</div>
                            <div class="font-semibold">{{ $ebook->metadata['isbn'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reviews -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Reviews</h2>
                <div class="bg-white rounded-xl p-6 border border-neutral-200">
                    @if($ebook->reviews->count() > 0)
                        <div class="space-y-4">
                            @foreach($ebook->reviews as $review)
                                <div class="border-b border-neutral-200 pb-4 last:border-b-0">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="material-icons-round text-primary-600 text-sm">person</span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="font-semibold">{{ $review->user->name }}</div>
                                                <div class="flex items-center gap-1">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <span class="material-icons-round text-sm {{ $i <= $review->rating ? 'text-yellow-400' : 'text-neutral-300' }}">
                                                            {{ $i <= $review->rating ? 'star' : 'star_border' }}
                                                        </span>
                                                    @endfor
                                                </div>
                                            </div>
                                            <p class="text-neutral-700 text-sm">{{ $review->review }}</p>
                                            <div class="text-xs text-neutral-500 mt-2">
                                                {{ $review->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <span class="material-icons-round text-4xl text-neutral-300 mb-4">rate_review</span>
                            <p class="text-neutral-600">No reviews yet. Be the first to review this ebook!</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Author Info -->
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">About the Author</h3>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="material-icons-round text-2xl text-primary-600">person</span>
                    </div>
                    <div>
                        <div class="font-semibold">{{ $ebook->user->name }}</div>
                        <div class="text-sm text-neutral-600">Author</div>
                    </div>
                </div>
                <p class="text-neutral-600 text-sm">
                    Experienced author with expertise in {{ $ebook->category->name }} 
                    and passion for creating educational content.
                </p>
            </div>

            <!-- Similar Ebooks -->
            @if($similarEbooks->count() > 0)
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Similar Ebooks</h3>
                <div class="space-y-4">
                    @foreach($similarEbooks as $similarEbook)
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                @if($similarEbook->thumbnail)
                                    <img src="{{ $similarEbook->getThumbnailUrl() }}" 
                                         alt="{{ $similarEbook->title }}" 
                                         class="w-full h-full object-cover rounded">
                                @else
                                    <span class="material-icons-round text-primary-600 text-sm">menu_book</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm line-clamp-1">{{ $similarEbook->title }}</div>
                                <div class="text-xs text-neutral-600">{{ $similarEbook->user->name }}</div>
                            </div>
                            <div class="text-right">
                                @if($similarEbook->is_free)
                                    <div class="text-sm font-bold text-accent-green">Free</div>
                                @else
                                    <div class="text-sm font-bold">${{ number_format($similarEbook->price, 0) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recommended Ebooks -->
            @if(auth()->check() && $recommendedEbooks->count() > 0)
            <div class="bg-white rounded-xl p-6 border border-neutral-200">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Recommended for You</h3>
                <div class="space-y-4">
                    @foreach($recommendedEbooks as $recommendedEbook)
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-accent-purple/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                @if($recommendedEbook->thumbnail)
                                    <img src="{{ $recommendedEbook->getThumbnailUrl() }}" 
                                         alt="{{ $recommendedEbook->title }}" 
                                         class="w-full h-full object-cover rounded">
                                @else
                                    <span class="material-icons-round text-accent-purple text-sm">menu_book</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm line-clamp-1">{{ $recommendedEbook->title }}</div>
                                <div class="text-xs text-neutral-600">{{ $recommendedEbook->user->name }}</div>
                            </div>
                            <div class="text-right">
                                @if($recommendedEbook->is_free)
                                    <div class="text-sm font-bold text-accent-green">Free</div>
                                @else
                                    <div class="text-sm font-bold">${{ number_format($recommendedEbook->price, 0) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Purchase ebook
function purchaseEbook(ebookId) {
    fetch(`/zenithalms/payment/checkout`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_type: 'ebook',
            item_id: ebookId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect_url;
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

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
            
            // Update button text
            const buttonText = button.querySelector('span:last-child');
            if (buttonText) {
                buttonText.textContent = data.is_favorited ? 'Remove from Favorites' : 'Add to Favorites';
            }
            
            showNotification(data.message, data.status === 'added' ? 'success' : 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
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
</script>
@endsection
