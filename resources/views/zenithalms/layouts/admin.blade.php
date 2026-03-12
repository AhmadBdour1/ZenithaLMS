@extends('zenithalms.layouts.admin')

@section('title', 'Admin Dashboard - ZenithaLMS')

@section('content')
<!-- Admin Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Admin Dashboard</h1>
                <p class="text-xl text-primary-100">Welcome to ZenithaLMS Admin Panel</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold">{{ now()->format('l, F j, Y') }}</div>
                <div class="text-primary-100">{{ now()->format('g:i A') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ \App\Models\User::count() }}</div>
                <div class="text-sm text-neutral-600">Total Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ \App\Models\Course::where('is_published', true)->count() }}</div>
                <div class="text-sm text-neutral-600">Published Courses</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-accent-purple">{{ \App\Models\Enrollment::count() }}</div>
                <div class="text-sm text-neutral-600">Total Enrollments</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">${{ number_format(\App\Models\Payment::where('status', 'completed')->sum('amount'), 2) }}</div>
                <div class="text-sm text-neutral-600">Total Revenue</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent Activity -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Recent Activity</h2>
                    <a href="{{ route('zenithalms.admin.activity.index') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="divide-y divide-neutral-200">
                        <!-- Activity items would be loaded here -->
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-primary-600 text-sm">person</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">New user registered</div>
                                    <div class="text-sm text-neutral-600">{{ \App\Models\User::latest()->name }}</div>
                                    <div class="text-xs text-neutral-500 mt-1">{{ \App\Models\User::latest()->created_at->format('g:i A') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-green-600 text-sm">school</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">New course created</div>
                                    <div class="text-sm text-neutral-600">{{ \App\Models\Course::latest()->title }}</div>
                                    <div class="text-xs text-neutral-500 mt-1">{{ \App\Models\Course::latest()->created_at->format('g:i A') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-accent-purple rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons-round text-white text-sm">payments</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">Payment received</div>
                                    <div class="text-sm text-neutral-600">${{ number_format(\App\Models\Payment::latest()->amount, 2) }} USD</div>
                                    <div class="text-xs text-neutral-500 mt-1">{{ \App\Models\Payment::latest()->created_at->format('g:i A') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Users Management -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Users Management</h2>
                    <a href="{{ route('zenithalms.admin.users.index') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        Manage Users
                    </a>
                </div>
                
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-medium text-neutral-900">Quick Actions</div>
                                <div class="text-sm text-neutral-600">Common user management tasks</div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('zenithalms.admin.users.create') }}" 
                                   class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">add</span>
                                    Add User
                                </a>
                                <a href="{{ route('zenithalms.admin.users.import') }}" 
                                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">upload</span>
                                    Import Users
                                </a>
                                <a href="{{ route('zenithalms.admin.users.export') }}" 
                                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">download</span>
                                    Export Users
                                </a>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">Recent Users</div>
                                    <div class="text-sm text-neutral-600">Latest 5 registrations</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">User Roles</div>
                                    <div class="text-sm text-neutral-600">{{ \App\Models\Role::count() }} roles available</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Courses Management -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Courses Management</h2>
                    <a href="{{ route('zenithalms.admin.courses.index') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        Manage Courses
                    </a>
                </div>
                
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-medium text-neutral-900">Quick Actions</div>
                                <div class="text-sm text-neutral-600">Course management tasks</div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('zenithalms.admin.courses.create') }}" 
                                   class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">add</span>
                                    Create Course
                                </a>
                                <a href="{{ route('zenithalms.admin.courses.import') }}" 
                                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">upload</span>
                                    Import Courses
                                </a>
                                <a href="{{ route('zenithalms.admin.courses.export') }}" 
                                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors text-center">
                                    <span class="material-icons-round text-sm mr-2">download</span>
                                    Export Courses
                                </a>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">Course Statistics</div>
                                    <div class="text-sm text-neutral-600">{{ \App\Models\Course::count() }} total courses</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">Published Courses</div>
                                    <div class="text-sm text-neutral-600">{{ \App\Models\Course::where('is_published', true)->count() }} published</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Payments Overview -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-neutral-900">Payments Overview</h2>
                    <a href="{{ route('zenithalms.admin.payments.index') }}" class="text-primary-600 hover:text-primary-800 font-medium">
                        View All Payments
                    </a>
                </div>
                
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div class="text-sm font-medium text-neutral-900">Revenue</div>
                                <div class="text-2xl font-bold text-green-600">${{ number_format(\App\Models\Payment::where('status', 'completed')->sum('amount'), 2) }} USD</div>
                                <div class="text-sm text-neutral-600">Total revenue</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-neutral-900">This Month</div>
                                <div class="text-2xl font-bold text-primary-600">${{ number_format(\App\Models\Payment::where('status', 'completed')->where('created_at', '>=', now()->startOfMonth())->sum('amount'), 2) }} USD</div>
                                <div class="text-sm text-neutral-600">This month</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-neutral-900">Success Rate</div>
                                <div class="text-2xl font-bold text-green-600">{{ $this->calculatePaymentSuccessRate() }}%</div>
                                <div class="text-sm text-neutral-600">Success rate</div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="text-sm font-medium text-neutral-900">Recent Transactions</div>
                            <div class="space-y-2">
                                @php
                                    $recentPayments = \App\Models\Payment::with(['user', 'course'])
                                        ->orderBy('created_at', 'desc')
                                        ->take(5)
                                        ->get();
                                @endphp
                                @foreach($recentPayments as $payment)
                                    <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                            <span class="material-icons-round text-primary-600 text-sm">payments</span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-neutral-900">{{ $payment->user->name }}</div>
                                            <div class="text-sm text-neutral-600">{{ $payment->type }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-neutral-900">${{ number_format($payment->amount, 2) }} USD</div>
                                        <div class="text-xs text-neutral-500">{{ $payment->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- System Status -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">System Status</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">API Status</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Online</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Database</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Connected</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Cache Status</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Active</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Storage</span>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">75% Used</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('zenithalms.admin.backup.create') }}" 
                       class="w-full px-4 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center">
                        <span class="create_backup text-sm mr-2">create_backup</span>
                        Create Backup
                    </a>
                    
                    <a href="{{ route('zenithalms.admin.cache.clear') }}" 
                       class="w-full px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="clear_cache text-sm mr-2">clear_cache</span>
                        Clear Cache
                    </a>
                    
                    <a href="{{ route('zenithalms.settings.index') }}" 
                       class="w-full px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="settings text-sm mr-2">settings</span>
                        Settings
                    </a>
                    
                    <a href="{{ route('zenithalms.logs.index') }}" 
                       class="w-full px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="bug_report text-sm mr-2">bug_report</span>
                        View Logs
                    </a>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Notifications</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Unread</span>
                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                            {{ \App\Models\Notification::where('is_read', false)->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Total</span>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                            {{ \App\Models\Notification::count() }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Sent Today</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                            {{ \App\Models\Notification::where('created_at', '>=', now()->startOfDay())->count() }}
                        </span>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="{{ route('zenithalms.notifications.index') }}" 
                       class="w-full px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="notifications text-sm mr-2">notifications</span>
                        View All
                    </a>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">System Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">ZenithaLMS Version</span>
                        <span class="text-sm font-medium text-primary-600">v1.0.0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Laravel Version</span>
                        <span class="text-sm font-medium text-primary-600">{{ app()->version() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">PHP Version</span>
                        <span class="text-sm font-medium text-primary-600">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Database</span>
                        <span class="text-sm font-medium text-primary-600">{{ config('database.default') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Environment</span>
                        <span class="text-sm font-medium text-primary-600">{{ config('app.env') }}</span>
                    </div>
                </div>
            </div>

            <!-- Analytics Overview -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Analytics Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Page Views Today</span>
                        <span class="text-sm font-medium text-primary-600">{{ $this->getPageViewsToday() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Active Users</span>
                        <span class="text-sm font-medium text-primary-600">{{ $this->getActiveUsersToday() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Courses Enrolled Today</span>
                        <span class="text-sm font-medium text-primary-600">{{ $this->getEnrollmentsToday() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Revenue Today</span>
                        <span class="text-sm font-medium text-primary-600">${{ $this->getRevenueToday() }}</span>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="{{ route('zenithalms.analytics.index') }}" 
                       class="w-full px-4 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center">
                        <span class="analytics text-sm mr-2">analytics</span>
                        View Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
// ZenithaLMS: Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh stats every 30 seconds
    setInterval(function() {
        refreshDashboardStats();
    }, 30000);
    
    // Initialize charts
    initializeCharts();
});

function refreshStats() {
    // Fetch updated stats from API
    fetch('/zenithalms/api/admin/dashboard')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard stats:', error);
        });
}

function updateDashboardStats(stats) {
    // Update user count
    const userCount = document.querySelector('.text-primary-600');
    if (userCount) {
        userCount.textContent = stats.users.total;
    }
    
    // Update course count
    const courseCount = document.querySelector('.text-green-600');
    if (courseCount) {
        courseCount.textContent = stats.courses.published;
    }
    
    // Update enrollment count
    const enrollmentCount = document.querySelector('.text-accent-purple');
    if (enrollmentCount) {
        enrollmentCount.textContent = stats.enrollments.total;
    }
    
    // Update revenue
    const revenueCount = document.querySelector('.text-yellow-600');
    if (revenueCount) {
        revenueCount.textContent = stats.revenue.total;
    }
}

function initializeCharts() {
    // Initialize dashboard charts
    const ctx = document.getElementById('dashboard-chart');
    
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: [1200, 1900, 3000, 5000, 4200, 6000],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Users',
                        data: [120, 190, 240, 310, 450, 580, 720],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.1
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                    display: false
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// ZenithaLMS: Show notification
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        type === 'info' ? 'bg-blue-500' : 'bg-gray-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, duration);
}

// ZenithaLMS: Confirm action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ZenithaLMS: Format number
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?=\d{3})+(?=\d{3}))/g, ',');
}

// ZenithaLMS: Get page views today
function getPageViewsToday() {
    // This would be calculated from analytics data
    return Math.floor(Math.random() * 100) + 50;
}

// ZenithaLMS: Get active users today
function getActiveUsersToday() {
    // This would be calculated from analytics data
    return Math.floor(Math.random() * 50) + 20;
}

// ZenithaLMS: Get enrollments today
function getEnrollmentsToday() {
    // This would be calculated from analytics data
    return Math.floor(Math.random() * 30) + 10;
}

// ZenithaLMS: Get revenue today
function getRevenueToday() {
    // This would be calculated from analytics data
    return Math.floor(Math.random() * 1000) + 500;
}

// ZenithaLMS: Calculate payment success rate
function calculatePaymentSuccessRate() {
    // This would be calculated from analytics data
    return Math.floor(Math.random() * 20) + 80;
}
</script>
@endsection
