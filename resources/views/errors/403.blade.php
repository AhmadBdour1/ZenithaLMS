@extends('zenithalms.layouts.app')

@section('title', 'Access Forbidden - ZenithaLMS')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-neutral-50">
    <div class="max-w-md w-full text-center px-4">
        <div class="mb-8">
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-4xl text-red-600">lock</span>
            </div>
            <h1 class="text-6xl font-bold text-neutral-900 mb-4">403</h1>
            <h2 class="text-2xl font-semibold text-neutral-800 mb-4">Access Forbidden</h2>
            <p class="text-neutral-600 mb-8">
                You don't have permission to access this page. 
                Please login or contact an administrator if you think this is an error.
            </p>
        </div>

        <div class="space-y-4">
            @if(Route::has('login'))
                <a href="{{ route('login') }}" 
                   class="inline-block px-6 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">login</span>
                    Login
                </a>
            @else
                <a href="/login" 
                   class="inline-block px-6 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">login</span>
                    Login
                </a>
            @endif
            
            @if(Route::has('dashboard'))
                <a href="{{ route('dashboard') }}" 
                   class="inline-block px-6 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">dashboard</span>
                    Dashboard
                </a>
            @else
                <a href="/dashboard" 
                   class="inline-block px-6 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">dashboard</span>
                    Dashboard
                </a>
            @endif
            
            <div class="text-sm text-neutral-500">
                Or return <a href="{{ url()->previous() }}">go back</a> to the previous page
            </div>
        </div>

        <!-- Support Info -->
        <div class="mt-8 p-4 bg-white rounded-xl border border-neutral-200">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Need Help?</h3>
            <p class="text-sm text-neutral-600 mb-4">
                If you believe this is an error, please contact your system administrator.
            </p>
            <div class="space-y-2">
                <a href="mailto:support@zenithalms.com" 
                   class="flex items-center justify-center gap-2 text-blue-600 hover:text-blue-800">
                    <span class="material-icons-round text-sm">email</span>
                    support@zenithalms.com
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
