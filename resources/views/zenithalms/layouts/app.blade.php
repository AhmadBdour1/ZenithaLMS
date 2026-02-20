<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZenithaLMS - Reach Your Learning Peak')</title>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Accessibility improvements -->
    <meta name="description" content="@yield('description', 'ZenithaLMS - Your AI-powered learning platform for modern education')">
    <meta name="keywords" content="learning, education, courses, ebooks, AI, online learning">
    <meta name="author" content="ZenithaLMS">
    
    <!-- Open Graph meta tags for social sharing -->
    <meta property="og:title" content="@yield('title', 'ZenithaLMS - Reach Your Learning Peak')">
    <meta property="og:description" content="@yield('description', 'Your AI-powered learning platform for modern education')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/zenithalms-og-image.png') }}">
    
    <!-- Twitter Card meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'ZenithaLMS - Reach Your Learning Peak')">
    <meta name="twitter:description" content="@yield('description', 'Your AI-powered learning platform for modern education')">
    <meta name="twitter:image" content="{{ asset('images/zenithalms-og-image.png') }}">
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#EEF2FF',
                            100: '#E0E7FF',
                            200: '#C7D2FE',
                            300: '#A5B4FC',
                            400: '#818CF8',
                            500: '#6366F1',
                            600: '#4F46E5',
                            700: '#4338CA',
                            800: '#3730A3',
                            900: '#312E81',
                        },
                        accent: {
                            purple: '#A855F7',
                            blue: '#3B82F6',
                            pink: '#EC4899',
                            green: '#10B981',
                            yellow: '#F59E0B',
                            red: '#EF4444',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    borderRadius: {
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.5); }
            50% { box-shadow: 0 0 30px rgba(99, 102, 241, 0.8); }
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .zenithalms-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .zenithalms-gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-neutral-50 font-sans">
    <!-- Skip to main content link for screen readers -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white p-2 rounded-md z-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
        {{ __('Skip to main content') }}
    </a>

    <!-- Navigation -->
    <nav role="navigation" aria-label="{{ __('Main navigation') }}" class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2" aria-label="ZenithaLMS {{ __('Home') }}">
                        <div class="w-8 h-8 zenithalms-gradient rounded-lg flex items-center justify-center" aria-hidden="true">
                            <span class="material-icons-round text-white text-sm">school</span>
                        </div>
                        <span class="font-bold text-xl zenithalms-gradient-text">{{ __('ZenithaLMS') }}</span>
                    </a>
                </div>

                <!-- Mobile menu button -->
                <button type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500" aria-expanded="false" aria-label="{{ __('Toggle main menu') }}">
                    <span class="material-icons-round">menu</span>
                </button>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Home') }}</a>
                    <a href="/courses" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Courses') }}</a>
                    <a href="/ebooks" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Ebooks') }}</a>
                    <a href="/blog" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Blog') }}</a>
                    <a href="/dashboard/instructor" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Instructor') }}</a>
                    <a href="/dashboard/student" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('Student') }}</a>
                    <a href="/ai/assistant" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2">{{ __('AI Assistant') }}</a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @guest
                        <a href="/login-enhanced" class="px-4 py-2 text-primary-600 hover:text-primary-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md" aria-label="{{ __('Sign in to your account') }}">
                            {{ __('Sign In') }}
                        </a>
                        <a href="/register" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500" aria-label="{{ __('Create a new account') }}">
                            {{ __('Get Started') }}
                        </a>
                        <a href="/login-enhanced" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all transform hover:scale-105 shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500" aria-label="{{ __('Quick access to dashboard') }}">
                            <span class="flex items-center gap-2">
                                <span class="material-icons-round text-sm" aria-hidden="true">speed</span>
                                {{ __('Quick Access') }}
                            </span>
                        </a>
                    @endguest
                    
                    @auth
                        <div class="flex items-center space-x-4">
                            <a href="/dashboard/student" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2" aria-label="{{ __('Go to dashboard') }}">
                                {{ __('Dashboard') }}
                            </a>
                            <a href="/profile" class="text-neutral-600 hover:text-primary-600 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2" aria-label="{{ __('View your profile') }}">
                                {{ __('Profile') }}
                            </a>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-primary-600 hover:text-primary-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md" aria-label="{{ __('Sign out of your account') }}">
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="main-content" role="main" aria-label="{{ __('Main content') }}">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer role="contentinfo" aria-label="{{ __('Site information') }}" class="bg-neutral-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 zenithalms-gradient rounded-lg flex items-center justify-center" aria-hidden="true">
                            <span class="material-icons-round text-white text-sm">school</span>
                        </div>
                        <span class="font-bold text-xl">{{ __('ZenithaLMS') }}</span>
                    </div>
                    <p class="text-neutral-400">
                        {{ __('Your AI-powered learning platform for modern education.') }}
                    </p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('Platform') }}</h3>
                    <ul class="space-y-2 text-neutral-400">
                        <li><a href="/courses" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Courses') }}</a></li>
                        <li><a href="/ebooks" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Ebooks') }}</a></li>
                        <li><a href="/blog" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Blog') }}</a></li>
                        <li><a href="/ai/assistant" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('AI Assistant') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('Resources') }}</h3>
                    <ul class="space-y-2 text-neutral-400">
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Help Center') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Documentation') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Blog') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Community') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('Company') }}</h3>
                    <ul class="space-y-2 text-neutral-400">
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('About Us') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Careers') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Contact') }}</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-white rounded-md p-1">{{ __('Privacy Policy') }}</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-neutral-800 mt-8 pt-8 text-center text-neutral-400">
                <p>&copy; {{ date('Y') }} {{ __('ZenithaLMS. All rights reserved.') }}</p>
            </div>
        </div>
    </footer>
    
    @stack('scripts')
    
    <!-- ZenithaLMS: Global JavaScript -->
    <script>
        // ZenithaLMS: Global functions
        function showNotification(message, type = 'info', duration = 3000) {
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

        // ZenithaLMS: AJAX helper
        function ajaxRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                ...options
            };

            return fetch(url, defaultOptions)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                });
        }

        // ZenithaLMS: Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Add loading states to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="material-icons-round animate-spin">refresh</span> Processing...';
                    }
                });
            });
        });

        // ZenithaLMS: Dark mode toggle (if implemented)
        function toggleDarkMode() {
            document.body.classList.toggle('dark');
            localStorage.setItem('darkMode', document.body.classList.contains('dark'));
        }

        // ZenithaLMS: Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>
