@extends('zenithalms.layouts.app')

@section('title', 'Profile - ZenithaLMS')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-neutral-900 mb-2">Profile</h1>
                <p class="text-neutral-600">Manage your account settings and preferences.</p>
            </div>

            <!-- Profile Card -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="material-icons-round text-primary-600 text-2xl">person</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-neutral-900">{{ Auth::user()->name }}</h2>
                        <p class="text-neutral-600">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-neutral-200">
                        <span class="text-neutral-600">Member Since</span>
                        <span class="font-medium text-neutral-900">{{ Auth::user()->created_at->format('M d, Y') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-neutral-200">
                        <span class="text-neutral-600">Last Login</span>
                        <span class="font-medium text-neutral-900">{{ Auth::user()->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</span>
                    </div>

                    <div class="flex justify-between items-center py-3">
                        <span class="text-neutral-600">Account Status</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Active</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                        Edit Profile
                    </button>
                    <button class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors">
                        Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
