<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install ZenithaLMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">ZenithaLMS Installation</h1>
                <p class="mt-2 text-gray-600">Welcome to the ZenithaLMS installation wizard</p>
            </div>

            <div class="bg-white shadow rounded-lg">
                <!-- Requirements Check -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">System Requirements</h2>
                </div>
                <div class="px-6 py-4">
                    @foreach($requirements as $key => $requirement)
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm font-medium text-gray-700">{{ $key }}</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">{{ $requirement['current'] }}</span>
                                @if($requirement['status'] === 'ok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">✓</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">✗</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Permissions Check -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">File Permissions</h2>
                </div>
                <div class="px-6 py-4">
                    @foreach($permissions as $key => $permission)
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm font-medium text-gray-700">{{ $key }}</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">{{ $permission['current'] }}</span>
                                @if($permission['status'] === 'ok')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">✓</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">✗</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Database Check -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Database Connection</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm font-medium text-gray-700">Database</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ $dbConnection['current'] }}</span>
                            @if($dbConnection['status'] === 'ok')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">✓</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">✗</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Installation Form -->
                <div class="px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Complete Installation</h2>
                    <form id="installForm" class="space-y-4">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700">Site Name</label>
                            <input type="text" id="site_name" name="site_name" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   value="ZenithaLMS">
                        </div>
                        
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700">Admin Name</label>
                            <input type="text" id="admin_name" name="admin_name" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700">Admin Password</label>
                            <input type="password" id="admin_password" name="admin_password" required minlength="8"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" id="installBtn"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                <span id="installBtnText">Install ZenithaLMS</span>
                                <span id="installSpinner" class="hidden ml-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                    
                    <div id="installMessage" class="mt-4 hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('installForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('installBtn');
            const btnText = document.getElementById('installBtnText');
            const spinner = document.getElementById('installSpinner');
            const message = document.getElementById('installMessage');
            
            // Show loading state
            btn.disabled = true;
            btnText.textContent = 'Installing...';
            spinner.classList.remove('hidden');
            message.classList.add('hidden');
            
            try {
                const formData = new FormData(this);
                const response = await fetch('/install/run', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    message.className = 'mt-4 p-4 rounded-md bg-green-50 border border-green-200';
                    message.innerHTML = `
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Installation Complete!</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>ZenithaLMS has been successfully installed.</p>
                                    <p class="mt-1">Redirecting to login page...</p>
                                </div>
                            </div>
                        </div>
                    `;
                    message.classList.remove('hidden');
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 2000);
                } else {
                    message.className = 'mt-4 p-4 rounded-md bg-red-50 border border-red-200';
                    message.innerHTML = `
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Installation Failed</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>${result.message}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    message.classList.remove('hidden');
                }
            } catch (error) {
                message.className = 'mt-4 p-4 rounded-md bg-red-50 border border-red-200';
                message.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Network Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>Unable to connect to the server. Please check your connection and try again.</p>
                            </div>
                        </div>
                    </div>
                `;
                message.classList.remove('hidden');
            } finally {
                // Reset button state
                btn.disabled = false;
                btnText.textContent = 'Install ZenithaLMS';
                spinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
