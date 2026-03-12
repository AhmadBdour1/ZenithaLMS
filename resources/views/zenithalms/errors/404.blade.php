@extends('zenithalms.layouts.admin')

@section('title', 'Page Not Found - ZenithaLMS')

@section('content')
<!-- 404 Error Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 to-accent-purple/20 flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <!-- 404 Icon -->
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-8">
            <span class="material-icons-round text-yellow-600 text-3xl">search_off</span>
        </div>
        
        <!-- 404 Title -->
        <h1 class="text-3xl font-bold text-neutral-900 mb-4">
            404 - Page Not Found
        </h1>
        
        <!-- Error Message -->
        <p class="text-lg text-neutral-600 mb-8">
            Oops! The page you're looking for doesn't exist or has been moved.
        </p>
        
        <!-- Search Box -->
        <div class="mb-8">
            <form onsubmit="searchSite(event)" class="flex gap-2">
                <input type="text" 
                       id="search-input"
                       placeholder="Search ZenithaLMS..." 
                       class="flex-1 px-4 py-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <button type="submit" 
                        class="px-4 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm">search</span>
                </button>
            </form>
        </div>
        
        <!-- Popular Links -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Popular Pages</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ url('/') }}" 
                   class="px-4 py-2 bg-white border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center">
                    <span class="material-icons-round text-sm mr-2">home</span>
                    Home
                </a>
                <a href="{{ url('/courses') }}" 
                   class="px-4 py-2 bg-white border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center">
                    <span class="material-icons-round text-sm mr-2">school</span>
                    Courses
                </a>
                <a href="{{ url('/ebooks') }}" 
                   class="px-4 py-2 bg-white border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center">
                    <span class="material-icons-round text-sm mr-2">menu_book</span>
                    Ebooks
                </a>
                <a href="{{ url('/forum') }}" 
                   class="px-4 py-2 bg-white border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center">
                    <span class="material-icons-round text-sm mr-2">forum</span>
                    Forum
                </a>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-4">
            <button onclick="goBack()" 
                    class="w-full px-6 py-3 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                <span class="material-icons-round text-sm mr-2">arrow_back</span>
                Go Back
            </button>
            
            <a href="{{ url('/') }}" 
               class="w-full px-6 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                <span class="material-icons-round text-sm mr-2">home</span>
                Go Home
            </a>
        </div>
        
        <!-- Support Info -->
        <div class="mt-8 p-4 bg-white rounded-xl border border-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Still Can't Find It?</h3>
            <p class="text-sm text-neutral-600 mb-4">
                Try using our search bar above or contact our support team for assistance.
            </p>
            <div class="space-y-2">
                <a href="mailto:support@zenithalms.com" 
                   class="flex items-center justify-center gap-2 text-primary-600 hover:text-primary-800">
                    <span class="material-icons-round text-sm">email</span>
                    support@zenithalms.com
                </a>
                <a href="tel:+1234567890" 
                   class="flex items-center justify-center gap-2 text-primary-600 hover:text-primary-800">
                    <span class="material-icons-round text-sm">phone</span>
                    +1 (234) 567-890
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: 404 Error Page JavaScript
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}

function searchSite(event) {
    event.preventDefault();
    const searchTerm = document.getElementById('search-input').value;
    if (searchTerm.trim()) {
        window.location.href = `/search?q=${encodeURIComponent(searchTerm)}`;
    }
}

// Auto-focus search input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search-input').focus();
});
</script>
@endsection
