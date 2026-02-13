@extends('zenithalms.layouts.app')

@section('title', 'Admin Dashboard - ZenithaLMS')

@push('styles')
<style>
.admin-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}
.quick-action-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.quick-action-card:hover {
    border-color: #667eea;
    transform: translateY(-3px);
}
.chart-container {
    position: relative;
    height: 300px;
}
.activity-item {
    transition: all 0.2s ease;
}
.activity-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
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
                        <span class="material-icons-round text-3xl">admin_panel_settings</span>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-3xl font-bold">Admin Dashboard</h1>
                        <p class="text-red-100">Complete System Control Center</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-red-100">Welcome back</p>
                        <p class="font-semibold">{{ Auth::user()->name }}</p>
                    </div>
                    <div class="relative">
                        <button class="p-2 bg-white/20 rounded-full hover:bg-white/30 transition-colors">
                            <span class="material-icons-round">notifications</span>
                            <span class="absolute top-0 right-0 w-3 h-3 bg-yellow-400 rounded-full"></span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors font-semibold">
                            <span class="material-icons-round text-sm">logout</span>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Enhanced Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm">Total Users</p>
                        <p class="text-3xl font-bold">{{ App\Models\User::count() }}</p>
                        <p class="text-white/70 text-xs mt-1">
                            <span class="text-green-300">↑ 12%</span> from last month
                        </p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-full">
                        <span class="material-icons-round text-3xl">people</span>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl shadow-lg p-6" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm">Total Courses</p>
                        <p class="text-3xl font-bold">{{ App\Models\Course::count() }}</p>
                        <p class="text-white/70 text-xs mt-1">
                            <span class="text-green-300">↑ 8%</span> from last month
                        </p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-full">
                        <span class="material-icons-round text-3xl">school</span>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl shadow-lg p-6" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm">Total Ebooks</p>
                        <p class="text-3xl font-bold">{{ App\Models\Ebook::count() }}</p>
                        <p class="text-white/70 text-xs mt-1">
                            <span class="text-green-300">↑ 15%</span> from last month
                        </p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-full">
                        <span class="material-icons-round text-3xl">menu_book</span>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl shadow-lg p-6" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm">Revenue</p>
                        <p class="text-3xl font-bold">$24,580</p>
                        <p class="text-white/70 text-xs mt-1">
                            <span class="text-green-300">↑ 23%</span> from last month
                        </p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-full">
                        <span class="material-icons-round text-3xl">payments</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- User Management -->
            <div class="bg-white rounded-xl shadow-lg p-6 admin-card" style="border-left-color: #ef4444;">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-red-100 rounded-full">
                        <span class="material-icons-round text-red-600">people</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">User Management</h3>
                </div>
                <p class="text-gray-600 mb-4">Manage all system users, roles, and permissions</p>
                <div class="space-y-2">
                    <a href="/admin/users" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-red-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">All Users</span>
                            <span class="text-sm text-gray-500">{{ App\Models\User::count() }}</span>
                        </div>
                    </a>
                    <a href="/admin/users/create" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-red-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">add</span>
                            <span class="font-medium">Add New User</span>
                        </div>
                    </a>
                    <a href="/admin/roles" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-red-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">admin_panel_settings</span>
                            <span class="font-medium">Manage Roles</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Course Management -->
            <div class="bg-white rounded-xl shadow-lg p-6 admin-card" style="border-left-color: #3b82f6;">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <span class="material-icons-round text-blue-600">school</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">Course Management</h3>
                </div>
                <p class="text-gray-600 mb-4">Create and manage all courses and content</p>
                <div class="space-y-2">
                    <a href="/admin/courses" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">All Courses</span>
                            <span class="text-sm text-gray-500">{{ App\Models\Course::count() }}</span>
                        </div>
                    </a>
                    <a href="/admin/courses/create" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">add</span>
                            <span class="font-medium">Create Course</span>
                        </div>
                    </a>
                    <a href="/admin/categories" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">category</span>
                            <span class="font-medium">Categories</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Content Management -->
            <div class="bg-white rounded-xl shadow-lg p-6 admin-card" style="border-left-color: #10b981;">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 rounded-full">
                        <span class="material-icons-round text-green-600">menu_book</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">Content Management</h3>
                </div>
                <p class="text-gray-600 mb-4">Manage ebooks, lessons, and multimedia content</p>
                <div class="space-y-2">
                    <a href="/admin/ebooks" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-green-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">Ebooks</span>
                            <span class="text-sm text-gray-500">{{ App\Models\Ebook::count() }}</span>
                        </div>
                    </a>
                    <a href="/admin/lessons" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-green-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">video_library</span>
                            <span class="font-medium">Lessons</span>
                        </div>
                    </a>
                    <a href="/admin/quizzes" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-green-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">quiz</span>
                            <span class="font-medium">Quizzes</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Analytics & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Analytics Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">System Analytics</h3>
                    <select class="px-3 py-1 border rounded-lg text-sm">
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Last 3 months</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Recent Activity</h3>
                    <a href="/admin/activity" class="text-blue-600 hover:text-blue-700 text-sm">View all</a>
                </div>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @php
                        $recentUsers = \App\Models\User::latest()->take(5)->get();
                        $recentCourses = \App\Models\Course::latest()->take(3)->get();
                    @endphp
                    @foreach($recentUsers as $user)
                        <div class="activity-item flex items-center p-3 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <span class="material-icons-round text-blue-600 text-sm">person_add</span>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">New user registered</p>
                                <p class="text-xs text-gray-500">{{ $user->name }} - {{ $user->email }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                    @foreach($recentCourses as $course)
                        <div class="activity-item flex items-center p-3 rounded-lg">
                            <div class="p-2 bg-green-100 rounded-full">
                                <span class="material-icons-round text-green-600 text-sm">school</span>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">New course created</p>
                                <p class="text-xs text-gray-500">{{ $course->title }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $course->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- System Settings & Health -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- System Health -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 rounded-full">
                        <span class="material-icons-round text-green-600">health_and_safety</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">System Health</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Database</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Healthy</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cache</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Storage</span>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">75% Full</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">API</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Online</span>
                    </div>
                </div>
            </div>

            <!-- Quick Settings -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <span class="material-icons-round text-purple-600">settings</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">Quick Settings</h3>
                </div>
                <div class="space-y-3">
                    <a href="/admin/settings/general" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-purple-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">tune</span>
                            <span class="font-medium">General Settings</span>
                        </div>
                    </a>
                    <a href="/admin/settings/email" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-purple-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">email</span>
                            <span class="font-medium">Email Configuration</span>
                        </div>
                    </a>
                    <a href="/admin/settings/security" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-purple-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">security</span>
                            <span class="font-medium">Security</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Reports -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-orange-100 rounded-full">
                        <span class="material-icons-round text-orange-600">assessment</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 ml-3">Reports</h3>
                </div>
                <div class="space-y-3">
                    <a href="/admin/reports/users" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">people</span>
                            <span class="font-medium">User Reports</span>
                        </div>
                    </a>
                    <a href="/admin/reports/revenue" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">payments</span>
                            <span class="font-medium">Revenue Reports</span>
                        </div>
                    </a>
                    <a href="/admin/reports/activity" class="block w-full text-left px-4 py-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition-colors">
                        <div class="flex items-center">
                            <span class="material-icons-round text-sm mr-2">trending_up</span>
                            <span class="font-medium">Activity Reports</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Analytics Chart
const ctx = document.getElementById('analyticsChart').getContext('2d');
const analyticsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Users',
            data: [12, 19, 3, 5, 2, 3, 9],
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4
        }, {
            label: 'Courses',
            data: [3, 6, 2, 4, 1, 4, 2],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Auto-refresh dashboard data
setInterval(() => {
    // Refresh stats every 30 seconds
    location.reload();
}, 30000);
</script>
@endpush
@endsection
