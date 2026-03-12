@extends('zenithalms.layouts.app')

@section('title', 'Users Management - ZenithaLMS Admin')

@push('styles')
<style>
.user-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
}
.role-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
}
.action-btn {
    transition: all 0.2s ease;
}
.action-btn:hover {
    transform: scale(1.05);
}
.filter-section {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Enhanced Header -->
    <div class="bg-gradient-to-r from-red-600 to-red-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="p-3 bg-white/20 rounded-full backdrop-blur-sm">
                        <span class="material-icons-round text-3xl">people</span>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-3xl font-bold">Users Management</h1>
                        <p class="text-red-100">Manage all system users and permissions</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/users/create" class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors font-semibold">
                        <span class="material-icons-round text-sm">add</span>
                        Add User
                    </a>
                    <button onclick="exportUsers()" class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors font-semibold">
                        <span class="material-icons-round text-sm">download</span>
                        Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters Section -->
    <div class="filter-section border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="relative">
                    <span class="material-icons-round absolute left-3 top-3 text-gray-400 text-sm">search</span>
                    <input type="text" 
                           id="search-input"
                           placeholder="Search users..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>
                
                <select id="role-filter" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="instructor">Instructor</option>
                    <option value="student">Student</option>
                    <option value="organization_admin">Organization Admin</option>
                </select>
                
                <select id="status-filter" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
                
                <input type="date" 
                       id="start-date" 
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                       placeholder="Start Date">
                
                <button onclick="applyFilters()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                    <span class="material-icons-round text-sm">filter_list</span>
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Users Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ App\Models\User::count() }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <span class="material-icons-round text-red-600">people</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ App\Models\User::where('is_active', true)->count() }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <span class="material-icons-round text-green-600">check_circle</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">New This Month</p>
                        <p class="text-2xl font-bold text-gray-900">{{ App\Models\User::whereMonth('created_at', now()->month)->count() }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <span class="material-icons-round text-blue-600">person_add</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Admin Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ App\Models\User::whereHas('role', function($q) { $q->where('name', 'admin'); })->count() }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <span class="material-icons-round text-purple-600">admin_panel_settings</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Users</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table" class="bg-white divide-y divide-gray-200">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="pagination-container" class="px-6 py-4 border-t border-gray-200">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    
    // Search functionality
    document.getElementById('search-input').addEventListener('input', function(e) {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            loadUsers();
        }, 500);
    });
    
    // Filter functionality
    document.getElementById('role-filter').addEventListener('change', loadUsers);
    document.getElementById('status-filter').addEventListener('change', loadUsers);
    document.getElementById('start-date').addEventListener('change', loadUsers);
});

function loadUsers() {
    const search = document.getElementById('search-input').value;
    const role = document.getElementById('role-filter').value;
    const status = document.getElementById('status-filter').value;
    const startDate = document.getElementById('start-date').value;
    
    // Build URL with parameters
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (role) params.append('role', role);
    if (status) params.append('status', status);
    if (startDate) params.append('start_date', startDate);
    
    // For now, load static data (in real app, this would be an API call)
    loadStaticUsers();
}

function loadStaticUsers() {
    const users = [
        {
            id: 1,
            name: 'John Anderson',
            email: 'admin@zenithalms.com',
            role: 'admin',
            is_active: true,
            created_at: '2024-01-15',
            last_login_at: '2024-02-12 10:30:00'
        },
        {
            id: 2,
            name: 'Sarah Mitchell',
            email: 'sarah.mitchell@techacademy.zenithalms.com',
            role: 'admin',
            is_active: true,
            created_at: '2024-01-20',
            last_login_at: '2024-02-11 15:45:00'
        },
        {
            id: 3,
            name: 'David Brown',
            email: 'david.brown@student.zenithalms.com',
            role: 'student',
            is_active: true,
            created_at: '2024-02-01',
            last_login_at: '2024-02-12 09:15:00'
        },
        {
            id: 4,
            name: 'Emma Wilson',
            email: 'emma.wilson@instructor.zenithalms.com',
            role: 'instructor',
            is_active: true,
            created_at: '2024-01-25',
            last_login_at: '2024-02-10 14:20:00'
        }
    ];
    
    updateUsersTable(users);
}

function updateUsersTable(users) {
    const tbody = document.getElementById('users-table');
    tbody.innerHTML = '';
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <span class="material-icons-round text-4xl text-gray-300 mb-2">search_off</span>
                    <p>No users found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        
        const roleColors = {
            'admin': 'bg-orange-100 text-orange-800',
            'instructor': 'bg-blue-100 text-blue-800',
            'student': 'bg-green-100 text-green-800',
            'organization_admin': 'bg-purple-100 text-purple-800'
        };
        
        const statusClass = user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const statusText = user.is_active ? 'Active' : 'Inactive';
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="user-avatar bg-gradient-to-r from-red-500 to-red-600">
                        ${user.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${user.name}</div>
                        <div class="text-sm text-gray-500">${user.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="role-badge ${roleColors[user.role] || 'bg-gray-100 text-gray-800'}">
                    ${user.role.replace('_', ' ').toUpperCase()}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${new Date(user.created_at).toLocaleDateString()}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="editUser(${user.id})" class="action-btn text-blue-600 hover:text-blue-900">
                        <span class="material-icons-round text-sm">edit</span>
                    </button>
                    <button onclick="viewUser(${user.id})" class="action-btn text-gray-600 hover:text-gray-900">
                        <span class="material-icons-round text-sm">visibility</span>
                    </button>
                    <button onclick="deleteUser(${user.id})" class="action-btn text-red-600 hover:text-red-900">
                        <span class="material-icons-round text-sm">delete</span>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function editUser(userId) {
    window.location.href = `/admin/users/${userId}/edit`;
}

function viewUser(userId) {
    window.location.href = `/admin/users/${userId}`;
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        // In real app, this would be an API call
        showNotification('User deleted successfully', 'success');
        loadUsers();
    }
}

function applyFilters() {
    loadUsers();
}

function exportUsers() {
    // In real app, this would trigger a download
    showNotification('Export feature coming soon!', 'info');
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 shadow-lg transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        'bg-blue-500'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="material-icons-round mr-2">${
                type === 'success' ? 'check_circle' : 
                type === 'error' ? 'error' : 
                type === 'warning' ? 'warning' : 
                'info'
            }</span>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}
</script>
@endpush
@endsection
