<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - ZenithaLMS</title>
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

    <div class="max-w-2xl mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold mb-6">Contact Us</h1>
        <p class="text-gray-600 mb-4">Have questions? We'd love to hear from you.</p>
        <p class="text-gray-600 mb-4">Email: support@zenithalms.com</p>
        <div class="text-center mt-8">
            <a href="{{ route('central.landing') }}" class="text-blue-600 hover:underline">← Back to home</a>
        </div>
    </div>
</body>
</html>
