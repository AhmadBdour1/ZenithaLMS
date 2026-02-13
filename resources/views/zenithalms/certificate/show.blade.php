@extends('zenithalms.layouts.app')

@section('title', 'Certificate Details - ZenithaLMS')

@section('content')
<!-- Certificate Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Certificate Details</h1>
            <p class="text-xl text-primary-100">Certificate #{{ $certificate->certificate_number }}</p>
        </div>
    </div>
</div>

<!-- Certificate Preview -->
<div class="bg-neutral-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Certificate Content -->
            <div class="p-8 text-center">
                <div class="mb-8">
                    <div class="text-4xl font-bold text-primary-600 mb-2">Certificate of Completion</div>
                    <div class="text-xl text-neutral-600">This is to certify that</div>
                </div>
                
                <div class="mb-8">
                    <div class="text-3xl font-bold text-neutral-900 mb-4">{{ $certificate->user->name }}</div>
                    <div class="text-lg text-neutral-700 mb-2">has successfully completed the course</div>
                    <div class="text-2xl font-bold text-primary-600 mb-2">{{ $certificate->course->title }}</div>
                    <div class="text-lg text-neutral-600">
                        with a grade of <span class="font-bold text-green-600">A+</span>
                    </div>
                </div>
                
                <div class="mb-8">
                    <div class="text-sm text-neutral-600 mb-2">Issued on</div>
                    <div class="text-lg font-medium text-neutral-900">{{ $certificate->issued_at->format('F j, Y') }}</div>
                </div>
                
                <div class="mb-8">
                    <div class="text-sm text-neutral-600 mb-2">Certificate Number</div>
                    <div class="text-lg font-mono text-primary-600">{{ $certificate->certificate_number }}</div>
                </div>
                
                <div class="mb-8">
                    <div class="text-sm text-neutral-600 mb-2">Verification Code</div>
                    <div class="text-lg font-mono text-primary-600">{{ $certificate->verification_code }}</div>
                </div>
                
                <div class="flex justify-center gap-8">
                    <div>
                        <div class="text-sm text-neutral-600 mb-1">Instructor</div>
                        <div class="font-medium text-neutral-900">{{ $certificate->course->instructor->name ?? 'ZenithaLMS' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-neutral-600 mb-1">Organization</div>
                        <div class="font-medium text-neutral-900">{{ $certificate->course->organization->name ?? 'ZenithaLMS' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Certificate Details -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Certificate Information -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-6">Certificate Information</h2>
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Certificate Number</div>
                            <div class="font-mono text-primary-600">{{ $certificate->certificate_number }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Student Name</div>
                            <div class="font-medium">{{ $certificate->user->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Course</div>
                            <div class="font-medium">{{ $certificate->course->title }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Instructor</div>
                            <div class="font-medium">{{ $certificate->course->instructor->name ?? 'ZenithaLMS' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Issued Date</div>
                            <div class="font-medium">{{ $certificate->issued_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Expiry Date</div>
                            <div class="font-medium {{ $certificate->expires_at && $certificate->expires_at->isPast() ? 'text-red-600' : 'text-neutral-900' }}">
                                {{ $certificate->expires_at ? $certificate->expires_at->format('M d, Y') : 'Never expires' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Verification Code</div>
                            <div class="font-mono text-primary-600">{{ $certificate->verification_code }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-neutral-600 mb-1">Status</div>
                            <div>
                                @if($certificate->expires_at && $certificate->expires_at->isPast())
                                    <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                        EXPIRED
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                        ACTIVE
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Blockchain Verification -->
            @if($certificate->blockchain_data)
                <section>
                    <h2 class="text-2xl font-bold text-neutral-900 mb-6">Blockchain Verification</h2>
                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-accent-purple rounded-lg flex items-center justify-center">
                                <span class="material-icons-round text-white text-xl">verified</span>
                            </div>
                            <div>
                                <div class="text-lg font-semibold text-neutral-900">Blockchain Verified</div>
                                <div class="text-sm text-neutral-600">This certificate is secured on the blockchain</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-neutral-600">Transaction ID</span>
                                <div class="font-mono text-xs text-primary-600">{{ substr($certificate->blockchain_data['transaction_id'] ?? '', 0, 32) }}...</div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-neutral-600">Block Hash</span>
                                <div class="font-mono text-xs text-primary-600">{{ substr($certificate->blockchain_data['block_hash'] ?? '', 0, 32) }}...</div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-neutral-600">Verified At</span>
                                <div class="text-sm text-neutral-900">{{ $certificate->blockchain_data['created_at'] ?? 'Unknown' }}</div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-neutral-600">Status</span>
                                <div class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                    {{ $certificate->blockchain_data['verified'] ? 'Verified' : 'Pending' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <!-- Verification Instructions -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-6">How to Verify</h2>
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-primary-600 text-sm">looks_one</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900 mb-1">Visit Verification Page</div>
                                <div class="text-sm text-neutral-600">Go to the certificate verification page and enter the verification code</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-primary-600 text-sm">looks_two</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900 mb-1">Enter Verification Code</div>
                                <div class="text-sm text-neutral-600">Input the verification code: {{ $certificate->verification_code }}</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-primary-600 text-sm">looks_3</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900 mb-1">Verify Certificate</div>
                                <div class="text-sm text-neutral-600">The system will verify the certificate's authenticity</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="material-icons-round text-primary-600 text-sm">verified</span>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900 mb-1">View Results</div>
                                <div class="text-sm text-neutral-600">See the verification results and certificate details</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Action Buttons -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('zenithalms.certificate.download', $certificate->certificate_number) }}" 
                       class="w-full px-4 py-3 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">download</span>
                        Download PDF
                    </a>
                    
                    <button onclick="shareCertificate('{{ $certificate->certificate_number }}')" 
                            class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors">
                        <span class="material-icons-round text-sm mr-2">share</span>
                        Share
                    </button>
                    
                    <a href="{{ route('zenithalms.certificate.add-to-linkedin', $certificate->certificate_number) }}" 
                       class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">work</span>
                        Add to LinkedIn
                    </a>
                    
                    <a href="{{ route('zenithalms.certificate.index') }}" 
                       class="w-full px-4 py-3 border border-neutral-300 text-neutral-700 rounded-lg font-semibold hover:border-primary-500 transition-colors text-center">
                        <span class="material-icons-round text-sm mr-2">school</span>
                        My Certificates
                    </a>
                </div>
            </div>

            <!-- QR Code -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Quick Verification</h3>
                <div class="text-center">
                    <div class="w-32 h-32 bg-neutral-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <span class="material-icons-round text-neutral-400 text-3xl">qr_code</span>
                    </div>
                    <p class="text-sm text-neutral-600 mb-4">Scan QR code for quick verification</p>
                    <div class="text-xs text-neutral-500">
                        Verification Code: {{ $certificate->verification_code }}
                    </div>
                </div>
            </div>

            <!-- Certificate Stats -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Views</span>
                        <span class="text-sm font-medium">{{ $certificate->view_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Downloads</span>
                        <span class="text-sm font-medium">{{ $certificate->download_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Shares</span>
                        <span class="text-sm font-medium">{{ $certificate->share_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Verification Count</span>
                        <span class="text-sm font-medium">{{ $certificate->verification_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Related Certificates -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Related Certificates</h3>
                <div class="space-y-3">
                    <!-- Related certificates would be loaded here -->
                    <p class="text-neutral-600 text-sm">Related certificates will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-neutral-900">Share Certificate</h3>
            <button onclick="closeShareModal()" class="text-neutral-400 hover:text-neutral-600">
                <span class="material-icons-round">close</span>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Share Link</label>
                <div class="flex gap-2">
                    <input type="text" id="share-link" readonly class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg bg-neutral-50" 
                           value="{{ route('zenithalms.certificate.show', $certificate->certificate_number) }}">
                    <button onclick="copyShareLink()" class="px-3 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                        <span class="material-icons-round text-sm">content_copy</span>
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">QR Code</label>
                <div id="qr-code" class="w-32 h-32 mx-auto bg-neutral-100 rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-neutral-400 text-3xl">qr_code</span>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button onclick="shareToTwitter()" class="flex-1 px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600">
                    <span class="material-icons-round text-sm mr-2">share</span>
                    Twitter
                </button>
                <button onclick="shareToLinkedIn()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <span class="material-icons-round text-sm mr-2">work</span>
                    LinkedIn
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Certificate JavaScript
function shareCertificate(certificateNumber) {
    fetch(`/zenithalms/certificate/share/${certificateNumber}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('share-link').value = data.share_url;
                
                // Load QR code
                if (data.qr_code) {
                    document.getElementById('qr-code').innerHTML = `<img src="${data.qr_code}" alt="QR Code" class="w-full h-full object-cover rounded-lg">`;
                }
                
                document.getElementById('share-modal').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error loading share data:', error);
            showNotification('Error loading share data', 'error');
        });
}

function closeShareModal() {
    document.getElementById('share-modal').classList.add('hidden');
}

function copyShareLink() {
    const shareLink = document.getElementById('share-link');
    shareLink.select();
    document.execCommand('copy');
    showNotification('Link copied to clipboard!', 'success');
}

function shareToTwitter() {
    const text = `I earned a certificate from ZenithaLMS! 🎓`;
    const url = encodeURIComponent(window.location.href);
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${url}`, '_blank');
}

function shareToLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
}

// ZenithaLMS: Show notification
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

// ZenithaLMS: Download functionality
function downloadCertificate() {
    window.open('{{ route('zenithalms.certificate.download', $certificate->certificate_number) }}', '_blank');
}

// ZenithaLMS: Print functionality
function printCertificate() {
    window.print();
}

// ZenithaLMS: Auto-update stats
setInterval(function() {
    // Update certificate stats every 30 seconds
    fetch(`/zenithalms/certificate/{{ $certificate->id }}/stats`)
        .then(response => response.json())
        .then(data => {
            // Update stats display
            const statsContainer = document.querySelector('.space-y-3');
            if (statsContainer) {
                // Update view count
                const viewCount = statsContainer.querySelector('.flex.justify-between:nth-child(1) .text-sm.font-medium');
                if (viewCount) {
                    viewCount.textContent = data.views;
                }
                
                // Update download count
                const downloadCount = statsContainer.querySelector('.flex.justify-between:nth-child(2) .text-sm.font-medium');
                if (downloadCount) {
                    downloadCount.textContent = data.downloads;
                }
                
                // Update share count
                const shareCount = statsContainer.querySelector('.flex.justify-between:nth-child(3) .text-sm.font-medium');
                if (shareCount) {
                    shareCount.textContent = data.shares;
                }
                
                // Update verification count
                const verificationCount = statsContainer.querySelector('.flex.justify-between:nth-child(4) .text-sm.font-medium');
                if (verificationCount) {
                    verificationCount.textContent = data.verification_count;
                }
            }
        })
        .catch(error => {
            console.error('Error updating stats:', error);
        });
}, 30000);
</script>
@endsection
