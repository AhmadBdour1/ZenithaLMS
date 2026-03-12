@extends('layouts.app')

@section('title', 'Admin Quick Login')

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Admin Quick Login
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Login as any user for support purposes
            </p>
        </div>

        <form id="quickLoginForm" class="mt-8 space-y-6" action="{{ route('admin.quick-login.login') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="user_search" class="block text-sm font-medium text-gray-700">
                        Search User
                    </label>
                    <div class="mt-1 relative">
                        <input type="text" id="user_search" name="user_search" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Search by name, email, or ID..." autocomplete="off">
                        <input type="hidden" id="user_id" name="user_id" required>
                        
                        <div id="user_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                            <!-- User results will be populated here -->
                        </div>
                    </div>
                </div>

                <div id="selected_user" class="hidden p-4 bg-gray-50 rounded-md">
                    <div class="flex items-center space-x-3">
                        <img id="selected_avatar" src="" alt="" class="h-10 w-10 rounded-full">
                        <div>
                            <p id="selected_name" class="font-medium text-gray-900"></p>
                            <p id="selected_email" class="text-sm text-gray-500"></p>
                            <p id="selected_role" class="text-xs text-gray-400"></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">
                        Reason (Optional)
                    </label>
                    <textarea id="reason" name="reason" rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                              placeholder="Why are you logging in as this user?"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Back to Admin Panel
                    </a>
                </div>
                
                <button type="submit" id="loginBtn" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                    Login as User
                </button>
            </div>
        </form>

        <div class="mt-6 border-t border-gray-200 pt-6">
            <div class="text-center">
                <p class="text-xs text-gray-500">
                    This action will be logged for security purposes.
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Only use this feature for legitimate support reasons.
                </p>
            </div>
        </div>
    </div>
</div>

@if(session()->has('admin_id'))
<div class="fixed bottom-4 right-4 bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded shadow-lg max-w-sm">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-yellow-700">
                You are currently logged in as a user. 
                <a href="{{ route('admin.quick-login.switch-back') }}" class="font-medium underline">Switch back to admin</a>
            </p>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    .user-result-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .user-result-item:hover {
        background-color: #f3f4f6;
    }
    
    .user-result-item.selected {
        background-color: #e0e7ff;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('user_search');
    const userResults = document.getElementById('user_results');
    const userIdInput = document.getElementById('user_id');
    const loginBtn = document.getElementById('loginBtn');
    const selectedUserDiv = document.getElementById('selected_user');
    let selectedUser = null;
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            userResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            searchUsers(query);
        }, 300);
    });

    function searchUsers(query) {
        fetch('{{ route("admin.quick-login.users") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                search: query,
                limit: 10
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUserResults(data.data);
            }
        })
        .catch(error => {
            console.error('Error searching users:', error);
        });
    }

    function displayUserResults(users) {
        if (users.length === 0) {
            userResults.innerHTML = '<div class="p-4 text-center text-gray-500">No users found</div>';
        } else {
            userResults.innerHTML = users.map(user => `
                <div class="user-result-item p-3 border-b border-gray-200 last:border-b-0" data-user-id="${user.id}" data-user='${JSON.stringify(user)}'>
                    <div class="flex items-center space-x-3">
                        <img src="${user.avatar || '/images/default-avatar.png'}" alt="${user.name}" class="h-8 w-8 rounded-full">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${user.name}</p>
                            <p class="text-sm text-gray-500">${user.email}</p>
                            <p class="text-xs text-gray-400">Role: ${user.role} • Last login: ${user.last_login_at || 'Never'}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        userResults.classList.remove('hidden');

        // Add click handlers
        document.querySelectorAll('.user-result-item').forEach(item => {
            item.addEventListener('click', function() {
                selectUser(this);
            });
        });
    }

    function selectUser(element) {
        // Remove previous selection
        document.querySelectorAll('.user-result-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Add selection to current
        element.classList.add('selected');

        // Get user data
        const userData = JSON.parse(element.dataset.user);
        selectedUser = userData;

        // Update hidden input
        userIdInput.value = userData.id;

        // Update selected user display
        document.getElementById('selected_avatar').src = userData.avatar || '/images/default-avatar.png';
        document.getElementById('selected_name').textContent = userData.name;
        document.getElementById('selected_email').textContent = userData.email;
        document.getElementById('selected_role').textContent = `Role: ${userData.role}`;

        selectedUserDiv.classList.remove('hidden');
        loginBtn.disabled = false;

        // Hide results
        userResults.classList.add('hidden');
        searchInput.value = userData.name;
    }

    // Click outside to close results
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !userResults.contains(event.target)) {
            userResults.classList.add('hidden');
        }
    });

    // Form submission
    document.getElementById('quickLoginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (!selectedUser) {
            alert('Please select a user to login as');
            return;
        }

        const formData = new FormData(this);
        formData.set('user_id', selectedUser.id);

        fetch(this.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect_url;
            } else {
                alert(data.message || 'Login failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    // Check admin session
    fetch('{{ route("admin.quick-login.check-session") }}')
        .then(response => response.json())
        .then(data => {
            if (data.has_admin_session && data.admin_user) {
                console.log('Admin session active:', data.admin_user);
            }
        });
});
</script>
@endpush
