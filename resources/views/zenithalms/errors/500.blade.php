@extends('zenithalms.layouts.admin')

@section('title', 'Error - ZenithaLMS')

@section('content')
<!-- Error Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 to-accent-purple/20 flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <!-- Error Icon -->
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-8">
            <span class="material-icons-round text-red-600 text-3xl">error</span>
        </div>
        
        <!-- Error Title -->
        <h1 class="text-3xl font-bold text-neutral-900 mb-4">
            {{ $statusCode ?? '500' }} Error
        </h1>
        
        <!-- Error Message -->
        <p class="text-lg text-neutral-600 mb-8">
            {{ $message ?? 'Something went wrong. We\'re working on it!' }}
        </p>
        
        <!-- Error Details (for development) -->
        @if(config('app.debug') && isset($exception))
            <div class="bg-white rounded-xl border border-neutral-200 p-6 mb-8 text-left">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Error Details</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm font-medium text-neutral-700">Type:</span>
                        <span class="text-sm text-neutral-600">{{ get_class($exception) }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-neutral-700">Message:</span>
                        <span class="text-sm text-neutral-600">{{ $exception->getMessage() }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-neutral-700">File:</span>
                        <span class="text-sm text-neutral-600">{{ $exception->getFile() }}:{{ $exception->getLine() }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-neutral-700">Trace:</span>
                        <pre class="text-xs text-neutral-600 bg-neutral-50 p-2 rounded overflow-x-auto">{{ $exception->getTraceAsString() }}</pre>
                    </div>
                </div>
            </div>
        @endif
        
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
            
            @if(config('app.debug'))
                <button onclick="refreshPage()" 
                        class="w-full px-6 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors">
                    <span class="material-icons-round text-sm mr-2">refresh</span>
                    Refresh Page
                </button>
            @endif
        </div>
        
        <!-- Support Info -->
        <div class="mt-8 p-4 bg-white rounded-xl border border-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Need Help?</h3>
            <p class="text-sm text-neutral-600 mb-4">
                If you continue to experience issues, please contact our support team.
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
// ZenithaLMS: Error Page JavaScript
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}

function refreshPage() {
    window.location.reload();
}

// Auto-refresh after 30 seconds (for production)
@if(!config('app.debug'))
    setTimeout(function() {
        window.location.reload();
    }, 30000);
@endif
</script>
@endsection
