@extends('zenithalms.layouts.app')

@section('title', 'Virtual Class Details - ZenithaLMS')

@section('content')
<!-- Virtual Class Header -->
<div class="bg-gradient-to-r from-primary-500 to-accent-purple text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $virtualClass->title }}</h1>
            <p class="text-xl text-primary-100 max-w-3xl mx-auto">{{ Str::limit($virtualClass->description, 200) }}</p>
        </div>
    </div>
</div>

<!-- Class Status Bar -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Status Badge -->
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 bg-{{ 
                    $virtualClass->status === 'live' ? 'red' : 
                    ($virtualClass->status === 'scheduled' ? 'blue' : 
                    ($virtualClass->status === 'ended' ? 'gray' : 'yellow') 
                }}-500 text-white text-sm font-bold rounded-full animate-pulse">
                    {{ strtoupper($virtualClass->status) }}
                </span>
                
                @if($virtualClass->status === 'live')
                    <span class="text-sm text-red-600 font-medium">LIVE NOW</span>
                @endif
            </div>

            <!-- Class Info -->
            <div class="flex items-center gap-6 text-sm text-neutral-600">
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">schedule</span>
                    <span>{{ $virtualClass->scheduled_at->format('M d, Y g:i A') }}</span>
                </div>
                
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">timer</span>
                    <span>{{ $virtualClass->duration_minutes }} min</span>
                </div>
                
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">group</span>
                    <span>{{ $virtualClass->current_participants }}/{{ $virtualClass->max_participants }}</span>
                </div>
                
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">videocam</span>
                    <span>{{ ucfirst($virtualClass->platform) }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 ml-auto">
                @if($virtualClass->status === 'live')
                    @if(auth()->check())
                        <button onclick="joinClass({{ $virtualClass->id }})" 
                                class="px-6 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                            <span class="material-icons-round text-sm mr-2">live_tv</span>
                            Join Now
                        </button>
                    @else
                        <a href="{{ route('login') }}" 
                           class="px-6 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                            <span class="material-icons-round text-sm mr-2">login</span>
                            Login to Join
                        </a>
                    @endif
                @elseif($virtualClass->status === 'scheduled')
                    @if(auth()->check())
                        <button onclick="scheduleReminder({{ $virtualClass->id }})" 
                                class="px-6 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                            <span class="material-icons-round text-sm mr-2">notification_add</span>
                            Set Reminder
                        </button>
                    @else
                        <a href="{{ route('login') }}" 
                           class="px-6 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                            <span class="material-icons-round text-sm mr-2">login</span>
                            Login to Join
                        </a>
                    @endif
                @else
                    @if(auth()->check())
                        <a href="{{ route('zenithalms.virtual-class.recording', $virtualClass->id) }}" 
                           class="px-6 py-2 bg-neutral-600 text-white rounded-lg font-semibold hover:bg-neutral-700 transition-colors">
                            <span class="material-icons-round text-sm mr-2">play_circle</span>
                            Watch Recording
                        </a>
                    @else
                        <button disabled 
                                class="px-6 py-2 bg-neutral-300 text-neutral-500 rounded-lg font-semibold cursor-not-allowed">
                            <span class="material-icons-round text-sm mr-2">block</span>
                            Class Ended
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Class Description -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">About This Class</h2>
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="prose max-w-none">
                        {!! $virtualClass->description !!}
                    </div>
                    
                    <!-- Class Tags -->
                    @if($virtualClass->tags && count($virtualClass->tags) > 0)
                        <div class="mt-6 pt-6 border-t border-neutral-200">
                            <div class="flex flex-wrap gap-2">
                                @foreach($virtualClass->tags as $tag)
                                    <span class="px-3 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Instructor Info -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Instructor</h2>
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="material-icons-round text-primary-600">person</span>
                        </div>
                        <div>
                            <div class="font-semibold text-neutral-900">{{ $virtualClass->instructor->name }}</div>
                            <div class="text-sm text-neutral-600">{{ $virtualClass->instructor->title ?? 'Instructor' }}</div>
                            <div class="text-sm text-neutral-600">{{ $virtualClass->instructor->email }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Course Info -->
            @if($virtualClass->course)
                <section>
                    <h2 class="text-2xl font-bold text-neutral-900 mb-4">Related Course</h2>
                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                <span class="material-icons-round text-primary-600">school</span>
                            </div>
                            <div>
                                <div class="font-semibold text-neutral-900">{{ $virtualClass->course->title }}</div>
                                <div class="text-sm text-neutral-600">{{ $virtualClass->course->description }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <!-- Meeting Details -->
            <section>
                <h2 class="text-2xl font-bold text-neutral-900 mb-4">Meeting Details</h2>
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Platform</div>
                            <div class="text-neutral-900">{{ ucfirst($virtualClass->platform) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Meeting ID</div>
                            <div class="text-primary-600 font-mono">{{ $virtualClass->meeting_id ?? 'Will be provided' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Password</div>
                            <div class="text-primary-600">{{ $virtualClass->password ?? 'No password required' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Duration</div>
                            <div class="text-neutral-900">{{ $virtualClass->duration_minutes }} minutes</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Max Participants</div>
                            <div class="text-neutral-900">{{ $virtualClass->max_participants }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-neutral-600 mb-1">Current Participants</div>
                            <div class="text-neutral-900">{{ $virtualClass->current_participants }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Participants -->
            @if($virtualClass->participants && $virtualClass->participants->count() > 0)
                <section>
                    <h2 class="text-2xl font-bold text-neutral-900 mb-4">Participants ({{ $virtualClass->participants->count() }})</h2>
                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <div class="space-y-3">
                            @foreach($virtualClass->participants->take(10) as $participant)
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                        <span class="material-icons-round text-primary-600 text-sm">person</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-neutral-900">{{ $participant->user->name }}</div>
                                        <div class="text-sm text-neutral-600">{{ $participant->joined_at->format('g:i A') }}</div>
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($virtualClass->participants->count() > 10)
                                <div class="text-center">
                                    <button class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                                        View all {{ $virtualClass->participants->count() }} participants
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- AI Insights -->
            @if($virtualClass->ai_analysis)
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">AI Insights</h3>
                    <div class="space-y-4">
                        <!-- Difficulty Level -->
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-icons-round text-accent-purple text-sm">analytics</span>
                                <span class="text-sm font-medium text-neutral-900">Difficulty Level</span>
                            </div>
                            <div class="px-2 py-1 bg-{{ 
                                $virtualClass->ai_analysis['difficulty_level'] === 'easy' ? 'green' : 
                                ($virtualClass->ai_analysis['difficulty_level'] === 'medium' ? 'yellow' : 'red') 
                            }}-100 text-{{ 
                                $virtualClass->ai_analysis['difficulty_level'] === 'easy' ? 'green' : 
                                ($virtualClass->ai_analysis['difficulty_level'] === 'medium' ? 'yellow' : 'red') 
                            }}-800 text-xs font-semibold rounded-full">
                                {{ ucfirst($virtualClass->ai_analysis['difficulty_level']) }}
                            </div>
                        </div>

                        <!-- Engagement Prediction -->
                        @if(isset($virtualClass->ai_analysis['estimated_engagement']))
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-icons-round text-accent-purple text-sm">trending_up</span>
                                    <span class="text-sm font-medium text-neutral-900">Expected Engagement</span>
                                </div>
                                <div class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                    {{ $virtualClass->ai_analysis['estimated_engagement'] }}
                                </div>
                            </div>
                        @endif

                        <!-- VR Compatibility -->
                        @if(isset($virtualClass->ai_analysis['vr_compatibility']))
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-icons-round text-accent-purple text-sm">view_in_ar</span>
                                    <span class="text-sm font-medium text-neutral-900">VR Ready</span>
                                </div>
                                <div class="px-2 py-1 bg-{{ 
                                    $virtualClass->ai_analysis['vr_compatibility']['compatible'] ? 'green' : 'gray' 
                                }}-100 text-{{ 
                                    $virtualClass->ai_analysis['vr_compatibility']['compatible'] ? 'green' : 'gray' 
                                }}-800 text-xs font-semibold rounded-full">
                                    {{ $virtualClass->ai_analysis['vr_compatibility']['compatible'] ? 'Compatible' : 'Not Compatible' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Schedule Reminder -->
            @if($virtualClass->status === 'scheduled' && auth()->check())
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Get Notified</h3>
                    <div class="space-y-3">
                        <p class="text-sm text-neutral-600">
                            Get notified when the class starts
                        </p>
                        <button onclick="scheduleReminder({{ $virtualClass->id }})" 
                                class="w-full px-4 py-2 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                            <span class="material-icons-round text-sm mr-2">notification_add</span>
                            Set Reminder
                        </button>
                    </div>
                </div>
            @endif

            <!-- Recording Info -->
            @if($virtualClass->status === 'ended' && $virtualClass->recording_url)
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Recording Available</h3>
                    <div class="space-y-3">
                        <p class="text-sm text-neutral-600">
                            Watch the recording of this class
                        </p>
                        <a href="{{ route('zenithalms.virtual-class.recording', $virtualClass->id) }}" 
                           class="w-full px-4 py-2 bg-neutral-600 text-white rounded-lg font-semibold hover:bg-neutral-700 transition-colors text-center">
                            <span class="material-icons-round text-sm mr-2">play_circle</span>
                            Watch Recording
                        </a>
                    </div>
                </div>
            @endif

            <!-- Materials -->
            @if($virtualClass->materials && count($virtualClass->materials) > 0)
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Class Materials</h3>
                    <div class="space-y-3">
                        @foreach($virtualClass->materials as $material)
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <span class="material-icons-round text-primary-600 text-sm">description</span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-neutral-900">{{ $material['name'] }}</div>
                                    <div class="text-sm text-neutral-600">{{ $material['type'] }}</div>
                                </div>
                                <a href="{{ $material['url'] }}" 
                                   class="px-3 py-1 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors">
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Related Classes -->
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Related Classes</h3>
                <div class="space-y-3">
                    <!-- Related classes would be loaded here -->
                    <p class="text-neutral-600 text-sm">Related classes will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Join Class Modal -->
<div id="join-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-red-600 text-2xl">live_tv</span>
            </div>
            <h3 class="text-lg font-bold text-neutral-900 mb-2">Join Virtual Class</h3>
            <p class="text-neutral-600 mb-6">You're about to join the live virtual class</p>
            
            <div class="flex gap-3">
                <button onclick="closeJoinModal()" 
                        class="flex-1 px-4 py-2 border border-neutral-300 rounded-lg hover:border-neutral-400 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmJoin()" 
                        class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    Join Now
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Virtual Class JavaScript
function joinClass(classId) {
    document.getElementById('join-modal').classList.remove('hidden');
}

function closeJoinModal() {
    document.getElementById('join-modal').classList.add('hidden');
}

function confirmJoin() {
    // Redirect to join URL
    window.location.href = '/zenithalms/virtual-class/join/{{ $virtualClass->id }}';
}

function scheduleReminder(classId) {
    fetch(`/zenithalms/virtual-class/reminder/${classId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Reminder set successfully!', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
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

// ZenithaLMS: Auto-refresh participant count
setInterval(function() {
    // Refresh participant count every 30 seconds if class is live
    if ('{{ $virtualClass->status }}' === 'live') {
        fetch(`/zenithalms/virtual-class/{{ $virtualClass->id }}/participants`)
            .then(response => response.json())
            .then(data => {
                // Update participant count
                const participantCount = document.querySelector('.flex.items-center.gap-1 .material-icons-round.text-sm.group + span');
                if (participantCount) {
                    participantCount.textContent = `${data.current_participants}/{{ $virtualClass->max_participants }}`;
                }
            })
            .catch(error => {
                console.error('Error updating participant count:', error);
            });
    }
}, 30000);

// ZenithaLMS: Countdown timer for scheduled classes
@if($virtualClass->status === 'scheduled')
    const scheduledTime = new Date('{{ $virtualClass->scheduled_at->toISOString() }}');
    const countdownElement = document.createElement('div');
    countdownElement.className = 'fixed top-4 right-4 px-4 py-2 bg-blue-500 text-white rounded-lg z-50';
    
    function updateCountdown() {
        const now = new Date();
        const timeDiff = scheduledTime - now;
        
        if (timeDiff > 0) {
            const hours = Math.floor(timeDiff / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
            
            let timeString = '';
            if (hours > 0) {
                timeString = `${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                timeString = `${minutes}m ${seconds}s`;
            } else {
                timeString = `${seconds}s`;
            }
            
            countdownElement.innerHTML = `<span class="material-icons-round text-sm mr-1">schedule</span>Class starts in ${timeString}`;
            countdownElement.style.display = 'block';
        } else {
            countdownElement.style.display = 'none';
            // Refresh page to show live status
            window.location.reload();
        }
    }
    
    document.body.appendChild(countdownElement);
    updateCountdown();
    setInterval(updateCountdown, 1000);
@endif
</script>
@endsection
