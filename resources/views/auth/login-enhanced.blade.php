<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - ZenithaLMS</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
</head>
<body class="font-sans antialiased bg-gradient-to-br from-primary-50 to-accent-purple/50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-primary-500 rounded-full flex items-center justify-center mb-4">
                    <span class="material-icons-round text-white text-2xl">school</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Welcome to ZenithaLMS</h2>
                <p class="mt-2 text-sm text-gray-600">Sign in to access your learning platform</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login Form -->
            <div class="bg-white shadow-xl rounded-2xl p-8">
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <input id="email" type="email" name="email" 
                               value="{{ old('email') }}" 
                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Enter your email"
                               required autofocus autocomplete="username">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input id="password" type="password" name="password" 
                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" 
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 text-sm text-gray-600">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            Sign In
                        </button>
                    </div>

                    <!-- Forgot Password -->
                    @if (Route::has('password.request'))
                        <div class="text-center">
                            <a href="{{ route('password.request') }}" 
                               class="text-sm text-primary-600 hover:text-primary-500 transition-colors">
                                Forgot your password?
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Quick Access Buttons -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Quick Access</h3>
                <div class="space-y-3">
                    <!-- Admin Dashboard -->
                    <button onclick="quickLogin('admin')" 
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium hover:from-red-600 hover:to-red-700 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons-round">admin_panel_settings</span>
                        Admin Dashboard
                    </button>

                    <!-- Instructor Dashboard -->
                    <button onclick="quickLogin('instructor')" 
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons-round">school</span>
                        Instructor Dashboard
                    </button>

                    <!-- Student Dashboard -->
                    <button onclick="quickLogin('student')" 
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons-round">person</span>
                        Student Dashboard
                    </button>

                    <!-- Organization Admin Dashboard -->
                    <button onclick="quickLogin('organization_admin')" 
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg font-medium hover:from-purple-600 hover:to-purple-700 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons-round">business</span>
                        Organization Admin Dashboard
                    </button>
                </div>

                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-xs text-yellow-800 text-center">
                        <strong>Development Mode:</strong> These buttons provide quick access for testing different user roles.
                    </p>
                </div>
            </div>

            <!-- Register Link -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500 transition-colors">
                        Sign up now
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Quick login function for development
        function quickLogin(role) {
            const credentials = {
                admin: { email: 'admin@zenithalms.com', password: 'password' },
                instructor: { email: 'instructor@zenithalms.com', password: 'password' },
                student: { email: 'student@zenithalms.com', password: 'password' },
                organization_admin: { email: 'org@zenithalms.com', password: 'password' }
            };

            const cred = credentials[role];
            if (cred) {
                document.getElementById('email').value = cred.email;
                document.getElementById('password').value = cred.password;
                
                // Show notification
                showNotification(`Filling credentials for ${role}...`, 'info');
                
                // Auto-submit after a short delay
                setTimeout(() => {
                    document.querySelector('form').submit();
                }, 1000);
            }
        }

        // Show notification function
        function showNotification(message, type = 'info') {
            const colors = {
                info: 'bg-blue-500',
                success: 'bg-green-500',
                warning: 'bg-yellow-500',
                error: 'bg-red-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${colors[type]} shadow-lg transform transition-all duration-300 translate-x-full`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to buttons
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
