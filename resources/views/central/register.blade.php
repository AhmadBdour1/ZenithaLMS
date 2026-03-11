<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Organization - ZenithaLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Your LMS</h1>
                <p class="text-gray-600">Start your 14-day free trial. No credit card required.</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white rounded-lg shadow-md p-8">
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('tenant.store') }}">
                    @csrf

                    <!-- Organization Details -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Organization Details</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                            <input type="text" name="organization_name" value="{{ old('organization_name') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="e.g., Acme University" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subdomain</label>
                            <div class="flex">
                                <input type="text" name="subdomain" value="{{ old('subdomain') }}" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                       placeholder="your-org" required>
                                <span class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-600">
                                    .zenithalms.test
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">This will be your unique LMS URL</p>
                        </div>
                    </div>

                    <!-- Admin Account -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Admin Account</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="admin_name" value="{{ old('admin_name') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="John Doe" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="admin@example.com" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" name="admin_password" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="Min. 8 characters" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" name="admin_password_confirmation" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <!-- Plan Selection -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Select Plan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($plans as $plan)
                                <label class="relative flex flex-col p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" 
                                           class="sr-only peer" 
                                           {{ $loop->first ? 'checked' : '' }} required>
                                    <div class="peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-500 absolute inset-0 rounded-lg"></div>
                                    <div class="text-center">
                                        <div class="font-bold text-lg mb-1">{{ $plan->name }}</div>
                                        <div class="text-2xl font-bold text-blue-600 mb-2">{{ $plan->formatted_price }}</div>
                                        <div class="text-sm text-gray-600 mb-2">{{ $plan->billing_cycle }}</div>
                                        <div class="text-sm text-gray-500">
                                            <div>{{ $plan->max_students }} students</div>
                                            <div>{{ $plan->max_courses }} courses</div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Create My LMS
                    </button>

                    <p class="text-center text-sm text-gray-500 mt-4">
                        By creating an account, you agree to our Terms of Service and Privacy Policy.
                    </p>
                </form>
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('central.landing') }}" class="text-blue-600 hover:underline">← Back to home</a>
            </div>
        </div>
    </div>
</body>
</html>
