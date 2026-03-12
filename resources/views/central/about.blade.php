<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - ZenithaLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('central.landing') }}" class="text-2xl font-bold text-blue-600">ZenithaLMS</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold mb-6">About ZenithaLMS</h1>
        <p class="text-lg text-gray-600 mb-4">
            ZenithaLMS is a powerful multi-tenant learning management system designed for educational institutions, 
            training organizations, and businesses.
        </p>
        <div class="text-center mt-8">
            <a href="{{ route('central.landing') }}" class="text-blue-600 hover:underline">← Back to home</a>
        </div>
    </div>
</body>
</html>
