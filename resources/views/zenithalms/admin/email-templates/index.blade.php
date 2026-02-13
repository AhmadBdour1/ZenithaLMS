@extends('zenithalms.layouts.admin')

@section('title', 'Email Templates - ZenithaLMS Admin')

@section('content')
<!-- Page Header -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Email Templates</h1>
                <p class="text-neutral-600">Manage email templates for notifications and communications</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('zenithalms.admin.email-templates.create') }}" 
                   class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">add</span>
                    Create Template
                </a>
                <a href="{{ route('zenithalms.admin.email-templates.test') }}" 
                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors">
                    <span class="material-icons-round text-sm mr-2">email</span>
                    Test Email
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Templates Grid -->
<div class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $template)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-lg mb-2">{{ $template->name }}</h3>
                                <div class="flex gap-2 mb-2">
                                    <span class="px-2 py-1 bg-{{ 
                                        $template->type === 'success' ? 'green' : 
                                        ($template->type === 'error' ? 'red' : 
                                        ($template->type === 'warning' ? 'yellow' : 
                                        ($template->type === 'info' ? 'blue' : 'gray'))
                                    }}-100 text-white text-xs font-semibold rounded-full">
                                        {{ ucfirst($template->type) }}
                                    </span>
                                    <span class="px-2 py-1 bg-{{ 
                                        $template->channel === 'email' ? 'blue' : 
                                        ($template->channel === 'push' ? 'purple' : 
                                        ($template->channel === 'sms' ? 'green' : 'gray')
                                    }}-100 text-white text-xs font-semibold rounded-full">
                                        {{ ucfirst($template->channel) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @if($template->is_default)
                                    <span class="px-2 py-1 bg-primary-100 text-primary-600 text-xs font-semibold rounded-full">
                                        Default
                                    </span>
                                @endif
                                @if($template->is_active)
                                    <span class="px-2 py-1 bg-green-100 text-green-600 text-xs font-semibold rounded-full">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-sm font-medium text-neutral-700 mb-1">Subject:</div>
                            <div class="text-sm text-neutral-600">{{ $template->subject_template }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-sm font-medium text-neutral-700 mb-1">Content Preview:</div>
                            <div class="text-sm text-neutral-600 line-clamp-3">{{ Str::limit($template->content_template, 150) }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-sm font-medium text-neutral-700 mb-1">Variables:</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach($template->variables as $variable)
                                    <span class="px-2 py-1 bg-neutral-100 text-neutral-600 text-xs rounded-full">
                                        {{ '{' . $variable . '}' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="{{ route('zenithalms.admin.email-templates.edit', $template->id) }}" 
                               class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                <span class="material-icons-round text-sm">edit</span>
                                Edit
                            </a>
                            <button onclick="previewTemplate({{ $template->id }})" 
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                <span class="material-icons-round text-sm">visibility</span>
                                Preview
                            </button>
                            <button onclick="testTemplate({{ $template->id }})" 
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                                <span class="material-icons-round text-sm">email</span>
                                Test
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-neutral-900">Email Template Preview</h3>
            <button onclick="closePreviewModal()" class="text-neutral-400 hover:text-neutral-600">
                <span class="material-icons-round">close</span>
            </button>
        </div>
        
        <div id="preview-content">
            <!-- Preview content will be loaded here -->
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div id="test-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-neutral-900">Test Email Template</h3>
            <button onclick="closeTestModal()" class="text-neutral-400 hover:text-neutral-600">
                <span class="material-icons-round">close</span>
            </button>
        </div>
        
        <form id="test-form" onsubmit="sendTestEmail(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 mb-2">Test Email Address:</label>
                <input type="email" 
                       id="test-email" 
                       required
                       class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                       placeholder="test@example.com">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 mb-2">Test Data (JSON):</label>
                <textarea id="test-data" 
                          rows="4"
                          class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                          placeholder='{"name": "John Doe", "course_name": "Test Course"}'></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">send</span>
                    Send Test Email
                </button>
                <button type="button" 
                        onclick="closeTestModal()"
                        class="flex-1 px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Email Templates JavaScript
let currentTemplateId = null;

function previewTemplate(templateId) {
    currentTemplateId = templateId;
    
    fetch(`/zenithalms/admin/email-templates/${templateId}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('preview-content').innerHTML = data.html;
                document.getElementById('preview-modal').classList.remove('hidden');
            } else {
                showNotification('Failed to load preview', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading preview:', error);
            showNotification('Error loading preview', 'error');
        });
}

function closePreviewModal() {
    document.getElementById('preview-modal').classList.add('hidden');
}

function testTemplate(templateId) {
    currentTemplateId = templateId;
    document.getElementById('test-modal').classList.remove('hidden');
}

function closeTestModal() {
    document.getElementById('test-modal').classList.add('hidden');
    document.getElementById('test-form').reset();
}

function sendTestEmail(event) {
    event.preventDefault();
    
    const email = document.getElementById('test-email').value;
    const testData = document.getElementById('test-data').value;
    
    let parsedData = {};
    if (testData) {
        try {
            parsedData = JSON.parse(testData);
        } catch (e) {
            showNotification('Invalid JSON format for test data', 'error');
            return;
        }
    }
    
    fetch(`/zenithalms/admin/email-templates/${currentTemplateId}/test`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            email: email,
            data: parsedData,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Test email sent successfully', 'success');
            closeTestModal();
        } else {
            showNotification(data.message || 'Failed to send test email', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending test email:', error);
        showNotification('Error sending test email', 'error');
    });
}

function showNotification(message, type = 'info', duration = 5000) {
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
</script>
@endsection
