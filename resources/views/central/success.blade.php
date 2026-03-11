<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success! - ZenithaLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <!-- Success Icon -->
                <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-4">🎉 Your LMS is Ready!</h1>
                
                <p class="text-lg text-gray-600 mb-8">
                    Your ZenithaLMS instance has been created successfully.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                    <h3 class="font-semibold text-gray-900 mb-4">Your Login Details</h3>
                    <div class="space-y-2 text-left">
                        <div class="flex justify-between">
                            <span class="text-gray-600">LMS URL:</span>
                            <a href="https://{{ $domain }}" class="text-blue-600 font-semibold hover:underline">
                                {{ $domain }}
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Admin Email:</span>
                            <span class="font-semibold">{{ $email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Trial Period:</span>
                            <span class="font-semibold">14 days</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <a href="https://{{ $domain }}/login" 
                       class="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Go to Your LMS Dashboard
                    </a>
                    <a href="{{ route('central.landing') }}" 
                       class="block w-full bg-white text-blue-600 border-2 border-blue-600 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                        Back to Home
                    </a>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h4 class="font-semibold mb-3">Next Steps:</h4>
                    <ul class="text-left space-y-2 text-gray-600">
                        <li>✓ Log in to your admin dashboard</li>
                        <li>✓ Customize your LMS branding</li>
                        <li>✓ Create your first course</li>
                        <li>✓ Invite instructors and students</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
