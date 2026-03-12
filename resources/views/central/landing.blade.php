<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZenithaLMS - Multi-Tenant Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">ZenithaLMS</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('central.pricing') }}" class="text-gray-700 hover:text-blue-600">Pricing</a>
                    <a href="{{ route('central.about') }}" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="{{ route('central.contact') }}" class="text-gray-700 hover:text-blue-600">Contact</a>
                    <a href="{{ route('tenant.register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <h2 class="text-5xl font-bold text-gray-900 mb-6">
                Your Complete Learning Management Platform
            </h2>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Create your own branded LMS in minutes. Manage courses, students, instructors, and more with ZenithaLMS - the most powerful multi-tenant learning platform.
            </p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('tenant.register') }}" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700">
                    Start Free Trial
                </a>
                <a href="{{ route('central.pricing') }}" class="bg-white text-blue-600 border-2 border-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-50">
                    View Pricing
                </a>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">🎓</div>
                <h3 class="text-xl font-semibold mb-2">Course Management</h3>
                <p class="text-gray-600">Create and manage unlimited courses with lessons, quizzes, and assessments.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">👥</div>
                <h3 class="text-xl font-semibold mb-2">User Management</h3>
                <p class="text-gray-600">Manage students, instructors, and administrators with role-based access.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">📊</div>
                <h3 class="text-xl font-semibold mb-2">Analytics & Reports</h3>
                <p class="text-gray-600">Track student progress, course completion, and generate detailed reports.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">💳</div>
                <h3 class="text-xl font-semibold mb-2">Payment Integration</h3>
                <p class="text-gray-600">Accept payments for courses with integrated payment gateways.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">🎨</div>
                <h3 class="text-xl font-semibold mb-2">Custom Branding</h3>
                <p class="text-gray-600">Customize colors, logos, and styling to match your brand.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="text-blue-600 text-4xl mb-4">📱</div>
                <h3 class="text-xl font-semibold mb-2">Mobile Responsive</h3>
                <p class="text-gray-600">Fully responsive design works perfectly on all devices.</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="mt-20 bg-blue-600 rounded-lg p-12 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2">1000+</div>
                    <div class="text-blue-100">Active Organizations</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">50K+</div>
                    <div class="text-blue-100">Students Learning</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">99.9%</div>
                    <div class="text-blue-100">Uptime Guarantee</div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="mt-20 text-center">
            <h3 class="text-3xl font-bold mb-4">Ready to get started?</h3>
            <p class="text-xl text-gray-600 mb-8">Create your LMS instance in less than 2 minutes.</p>
            <a href="{{ route('tenant.register') }}" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700">
                Create Your LMS Now
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400">© 2026 ZenithaLMS. Multi-Tenant Learning Management System.</p>
            </div>
        </div>
    </footer>
</body>
</html>
