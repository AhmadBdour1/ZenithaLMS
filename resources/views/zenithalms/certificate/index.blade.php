@extends('zenithalms.layouts.app')

@section('title', 'Certificates - ZenithaLMS')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">My Certificates</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">
                View and manage your blockchain-verified certificates
            </p>
        </div>
    </div>
</div>

<!-- Filters and Stats -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Stats -->
            <div class="flex-1 grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600">{{ $certificates->count() }}</div>
                    <div class="text-sm text-neutral-600">Total Certificates</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $certificates->where('expires_at', '>', now())->count() }}</div>
                    <div class="text-sm text-neutral-600">Active</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $certificates->where('expires_at', '<=', now())->count() }}</div>
                    <div class="text-sm text-neutral-600">Expired</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-accent-purple">{{ $certificates->where('blockchain_data', '!=', null)->count() }}</div>
                    <div class="text-sm text-neutral-600">Blockchain Verified</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <select name="course_id" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:border-primary-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Certificates Grid -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($certificates as $certificate)
                <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <!-- Certificate Preview -->
                    <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <span class="material-icons-round text-4xl text-primary-300 mb-2">school</span>
                                <div class="text-lg font-bold text-primary-600">Certificate</div>
                                <div class="text-sm text-primary-500">of Completion</div>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3">
                            @if($certificate->expires_at && $certificate->expires_at->isPast())
                                <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                    EXPIRED
                                </span>
                            @else
                                <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                                    ACTIVE
                                </span>
                            @endif
                        </div>

                        <!-- Blockchain Badge -->
                        @if($certificate->blockchain_data)
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 bg-accent-purple text-white text-xs font-bold rounded-full">
                                    BLOCKCHAIN
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Certificate Content -->
                    <div class="p-6">
                        <!-- Course Info -->
                        <div class="mb-4">
                            <h3 class="font-bold text-lg mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                                {{ $certificate->course->title }}
                            </h3>
                            <p class="text-neutral-600 text-sm">{{ $certificate->title }}</p>
                        </div>

                        <!-- Certificate Details -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-600">Certificate #</span>
                                <span class="font-mono text-xs">{{ $certificate->certificate_number }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-600">Issued</span>
                                <span>{{ $certificate->issued_at->format('M d, Y') }}</span>
                            </div>
                            @if($certificate->expires_at)
                                <div class="flex justify-between text-sm">
                                    <span class="text-neutral-600">Expires</span>
                                    <span class="{{ $certificate->expires_at->isPast() ? 'text-red-600' : 'text-neutral-900' }}">
                                        {{ $certificate->expires_at->format('M d, Y') }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-600">Verification</span>
                                <span class="font-mono text-xs">{{ $certificate->verification_code }}</span>
                            </div>
                        </div>

                        <!-- Blockchain Status -->
                        @if($certificate->blockchain_data)
                            <div class="mb-4 p-3 bg-accent-purple/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons-round text-accent-purple text-sm">verified</span>
                                    <span class="text-sm font-medium text-accent-purple">Blockchain Verified</span>
                                </div>
                                <div class="text-xs text-neutral-600 mt-1">
                                    Transaction ID: {{ substr($certificate->blockchain_data['transaction_id'] ?? '', 0, 16) }}...
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="{{ route('zenithalms.certificate.show', $certificate->certificate_number) }}" 
                               class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors text-center">
                                <span class="material-icons-round text-sm mr-2">visibility</span>
                                View
                            </a>
                            
                            <a href="{{ route('zenithalms.certificate.download', $certificate->certificate_number) }}" 
                               class="px-4 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors">
                                <span class="material-icons-round text-sm">download</span>
                            </a>
                            
                            <button onclick="shareCertificate('{{ $certificate->certificate_number }}')" 
                                    class="px-4 py-2 border border-neutral-300 rounded-xl hover:border-primary-500 transition-colors">
                                <span class="material-icons-round text-sm">share</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $certificates->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl text-neutral-400">school</span>
            </div>
            <h3 class="text-xl font-semibold text-neutral-900 mb-2">No certificates yet</h3>
            <p class="text-neutral-600 mb-6">Complete courses to earn certificates</p>
            <a href="{{ route('zenithalms.courses.index') }}" 
               class="px-6 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 transition-colors">
                Browse Courses
            </a>
        </div>
    @endif
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
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
                    <input type="text" id="shareLink" readonly class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg bg-neutral-50" value="">
                    <button onclick="copyShareLink()" class="px-3 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                        <span class="material-icons-round text-sm">content_copy</span>
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">QR Code</label>
                <div id="qrCode" class="w-32 h-32 mx-auto bg-neutral-100 rounded-lg flex items-center justify-center">
                    <span class="material-icons-round text-neutral-400">qr_code</span>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button onclick="shareToLinkedIn()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <span class="material-icons-round text-sm mr-2">work</span>
                    LinkedIn
                </button>
                <button onclick="shareToTwitter()" class="flex-1 px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600">
                    <span class="material-icons-round text-sm mr-2">share</span>
                    Twitter
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Share certificate functionality
let currentCertificateNumber = '';

function shareCertificate(certificateNumber) {
    currentCertificateNumber = certificateNumber;
    
    fetch(`/zenithalms/certificate/share/${certificateNumber}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('shareLink').value = data.share_url;
                
                // Load QR code
                if (data.qr_code) {
                    document.getElementById('qrCode').innerHTML = `<img src="${data.qr_code}" alt="QR Code" class="w-full h-full object-cover rounded-lg">`;
                }
                
                document.getElementById('shareModal').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading share data', 'error');
        });
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    shareLink.select();
    document.execCommand('copy');
    showNotification('Link copied to clipboard', 'success');
}

function shareToLinkedIn() {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(document.getElementById('shareLink').value)}`, '_blank');
}

function shareToTwitter() {
    window.open(`https://twitter.com/intent/tweet?text=Check out my certificate from ZenithaLMS!&url=${encodeURIComponent(document.getElementById('shareLink').value)}`, '_blank');
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

// ZenithaLMS: Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeShareModal();
    }
});

// ZenithaLMS: Close modal on background click
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});
</script>
@endsection
