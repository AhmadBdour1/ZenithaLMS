@extends('layouts.app')

@section('title', 'Quick Access - Demo Accounts')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Quick Access</h1>
            <p class="mt-2 text-gray-600">Demo accounts for testing different roles in ZenithaLMS</p>
        </div>

        <!-- Super Admin -->
        <div class="mb-8">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-red-800">Super Admin</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><strong>Email:</strong> admin@zenithalms.com</p>
                            <p><strong>Password:</strong> admin123</p>
                            <p class="mt-1">Full system access - can manage everything</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Organization Admins -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Organization Admins</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-blue-800">Tech Academy</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p><strong>Email:</strong> sarah.mitchell@techacademy.zenithalms.com</p>
                        <p><strong>Password:</strong> admin123</p>
                    </div>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-blue-800">Business School</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p><strong>Email:</strong> michael.chen@businessschool.zenithalms.com</p>
                        <p><strong>Password:</strong> admin123</p>
                    </div>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-blue-800">Creative Arts</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p><strong>Email:</strong> emma.dubois@creativearts.zenithalms.com</p>
                        <p><strong>Password:</strong> admin123</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructors -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Instructors</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-green-800">Dr. James Wilson</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p><strong>Email:</strong> james.wilson@techacademy.zenithalms.com</p>
                        <p><strong>Password:</strong> instructor123</p>
                        <p class="text-xs mt-1">Tech Academy - Computer Science</p>
                    </div>
                </div>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-green-800">Lisa Thompson</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p><strong>Email:</strong> lisa.thompson@techacademy.zenithalms.com</p>
                        <p><strong>Password:</strong> instructor123</p>
                        <p class="text-xs mt-1">Tech Academy - React Development</p>
                    </div>
                </div>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-green-800">Prof. Robert Johnson</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p><strong>Email:</strong> robert.johnson@businessschool.zenithalms.com</p>
                        <p><strong>Password:</strong> instructor123</p>
                        <p class="text-xs mt-1">Business School - Strategic Management</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teaching Assistant -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Teaching Assistant</h2>
            <div class="bg-purple-50 border-l-4 border-purple-400 p-4 rounded-lg max-w-md">
                <h3 class="text-lg font-medium text-purple-800">Alex Kumar</h3>
                <div class="mt-2 text-sm text-purple-700">
                    <p><strong>Email:</strong> alex.kumar@techacademy.zenithalms.com</p>
                    <p><strong>Password:</strong> ta123</p>
                    <p class="text-xs mt-1">Tech Academy - Computer Science TA</p>
                </div>
            </div>
        </div>

        <!-- Students -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Students</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">David Brown</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> david.brown@techacademy.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Tech Academy - Full-stack Development</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">Sophie Martin</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> sophie.martin@businessschool.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Business School - MBA Student</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">Pierre Laurent</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> pierre.laurent@creativearts.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Creative Arts - Digital Art</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">Emma Wilson</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> emma.wilson@techacademy.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Tech Academy - Frontend Development</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">James Taylor</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> james.taylor@businessschool.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Business School - Business Analytics</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800">Claire Rousseau</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>Email:</strong> claire.rousseau@creativearts.zenithalms.com</p>
                        <p><strong>Password:</strong> student123</p>
                        <p class="text-xs mt-1">Creative Arts - Photography</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Parent</h2>
            <div class="bg-pink-50 border-l-4 border-pink-400 p-4 rounded-lg max-w-md">
                <h3 class="text-lg font-medium text-pink-800">Jennifer Brown</h3>
                <div class="mt-2 text-sm text-pink-700">
                    <p><strong>Email:</strong> jennifer.brown@techacademy.zenithalms.com</p>
                    <p><strong>Password:</strong> parent123</p>
                    <p class="text-xs mt-1">Parent of David Brown - Can monitor progress</p>
                </div>
            </div>
        </div>

        <!-- Quick Login Buttons -->
        <div class="mt-12 bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Login</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <form method="POST" action="{{ route('login') }}" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="admin@zenithalms.com">
                    <input type="hidden" name="password" value="admin123">
                    <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                        Super Admin
                    </button>
                </form>
                <form method="POST" action="{{ route('login') }}" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="sarah.mitchell@techacademy.zenithalms.com">
                    <input type="hidden" name="password" value="admin123">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        Org Admin
                    </button>
                </form>
                <form method="POST" action="{{ route('login') }}" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="james.wilson@techacademy.zenithalms.com">
                    <input type="hidden" name="password" value="instructor123">
                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                        Instructor
                    </button>
                </form>
                <form method="POST" action="{{ route('login') }}" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="david.brown@techacademy.zenithalms.com">
                    <input type="hidden" name="password" value="student123">
                    <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                        Student
                    </button>
                </form>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-8 bg-gray-100 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-800">Security Notice</h3>
                    <div class="mt-2 text-sm text-gray-600">
                        <p>These are demo accounts for testing purposes only. In a production environment, always use strong, unique passwords.</p>
                        <p class="mt-1">All login attempts are logged for security monitoring.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
