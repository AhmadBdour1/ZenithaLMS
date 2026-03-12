<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - ZenithaLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-neutral-50">
    <div class="max-w-md w-full text-center px-4">
        <div class="mb-8">
            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-4xl text-blue-600">search_off</span>
            </div>
            <h1 class="text-6xl font-bold text-neutral-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-neutral-800 mb-4">Page Not Found</h2>
            <p class="text-neutral-600 mb-8">
                The page you're looking for doesn't exist or has been removed. 
                Browse our available content below.
            </p>
        </div>

        <div class="space-y-4">
            <a href="/courses" 
               class="inline-block px-6 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 transition-colors">
                Browse All Courses
            </a>
            
            <a href="/blog" 
               class="inline-block px-6 py-3 bg-purple-500 text-white rounded-xl font-semibold hover:bg-purple-600 transition-colors">
                Browse Blog
            </a>
            
            <a href="/ebooks" 
               class="inline-block px-6 py-3 bg-green-500 text-white rounded-xl font-semibold hover:bg-green-600 transition-colors">
                Browse Ebooks
            </a>
            
            <div class="text-sm text-neutral-500">
                Or <a href="/login" class="text-blue-600 hover:underline">login</a> to your account
            </div>
        </div>

        <!-- Popular Content -->
        <div class="mt-12">
            <h3 class="text-lg font-semibold text-neutral-800 mb-4">Popular Pages</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/courses" 
                   class="block p-4 bg-white rounded-lg border border-neutral-200 hover:border-blue-300 transition-colors">
                    <div class="text-center">
                        <span class="material-icons-round text-2xl text-blue-600">school</span>
                        <div class="mt-2 font-semibold text-neutral-800">Courses</div>
                    </div>
                </a>
                
                <a href="/blog" 
                   class="block p-4 bg-white rounded-lg border border-neutral-200 hover:border-purple-300 transition-colors">
                    <div class="text-center">
                        <span class="material-icons-round text-2xl text-purple-600">article</span>
                        <div class="mt-2 font-semibold text-neutral-800">Blog</div>
                    </div>
                </a>
                
                <a href="/ebooks" 
                   class="block p-4 bg-white rounded-lg border border-neutral-200 hover:border-green-300 transition-colors">
                    <div class="text-center">
                        <span class="material-icons-round text-2xl text-green-600">menu_book</span>
                        <div class="mt-2 font-semibold text-neutral-800">Ebooks</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
