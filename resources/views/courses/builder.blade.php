@extends('zenithalms.layouts.app')

@section('title', 'Course Builder - ' . $course->title)

@section('content')
<div class="course-builder">
    <!-- Header -->
    <div class="builder-header">
        <div class="header-left">
            <h1 class="text-2xl font-bold text-gray-900">{{ $course->title }}</h1>
            <div class="header-actions">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-outline">
                    <i class="material-icons">visibility</i>
                    Preview Course
                </a>
                <button class="btn btn-primary" onclick="saveStructure()">
                    <i class="material-icons">save</i>
                    Save Structure
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="builder-content">
        <!-- Course Structure -->
        <div class="course-structure">
            <div class="structure-header">
                <h3 class="text-lg font-semibold">Course Content</h3>
                <div class="structure-actions">
                    <button class="btn btn-primary btn-sm" onclick="addLesson()">
                        <i class="material-icons">add</i>
                        Add Lesson
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="addQuiz()">
                        <i class="material-icons">quiz</i>
                        Add Quiz
                    </button>
                </div>
            </div>
            
            <!-- Curriculum Items -->
            <div id="curriculum-items" class="curriculum-items">
                @foreach($curriculumItems as $item)
                <div class="curriculum-item {{ $item['type'] }}" data-id="{{ $item['id'] }}" data-type="{{ $item['type'] }}">
                    <div class="item-header drag-handle">
                        <div class="item-icon">
                            @if($item['type'] === 'lesson')
                                <i class="material-icons">play_circle</i>
                            @elseif($item['type'] === 'quiz')
                                <i class="material-icons">quiz</i>
                            @endif
                        </div>
                        <div class="item-content">
                            <input type="text" 
                                   value="{{ $item['title'] }}" 
                                   class="item-title" 
                                   placeholder="{{ $item['type'] === 'lesson' ? 'Lesson title' : 'Quiz title' }}"
                                   onchange="updateItem({{ $item['id'] }, '{{ $item['type'] }}')">
                            <div class="item-meta">
                                @if($item['type'] === 'lesson')
                                    @if($item['duration_minutes'])
                                        <span class="duration">{{ $item['duration_minutes'] }} min</span>
                                    @endif
                                    @if($item['is_free'])
                                        <span class="badge badge-success">Free</span>
                                    @endif
                                @else
                                    <span class="duration">{{ $item['time_limit_minutes'] }} min</span>
                                    <span class="badge badge-info">{{ $item['passing_score'] }}% pass</span>
                                @endif
                            </div>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-sm btn-outline" onclick="editItem({{ $item['id'] }, '{{ $item['type'] }}')">
                                <i class="material-icons">edit</i>
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="deleteItem({{ $item['id'] }, '{{ $item['type'] }}')">
                                <i class="material-icons">delete</i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Empty State -->
            @if($curriculumItems->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="material-icons" style="font-size: 48px;">school</i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700">No content yet</h3>
                <p class="text-gray-500">Start building your course by adding lessons and quizzes.</p>
                <div class="empty-actions">
                    <button class="btn btn-primary" onclick="addLesson()">
                        <i class="material-icons">add</i>
                        Add Your First Lesson
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Success Message -->
<div id="success-message" class="hidden fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg">
    <div class="flex items-center">
        <i class="material-icons mr-2">check_circle</i>
        <span id="success-text">Changes saved successfully!</span>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm Delete</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete this {{ request()->type }}? This action cannot be undone.</p>
        <div class="flex justify-end gap-3">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Course Builder JavaScript
let currentCourseId = {{ $course->id }};
let deleteItemType = null;
let deleteItemId = null;

// Initialize Sortable.js
document.addEventListener('DOMContentLoaded', function() {
    const curriculumItems = document.getElementById('curriculum-items');
    
    if (curriculumItems) {
        new Sortable(curriculumItems, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function(evt) {
                saveStructure();
            }
        });
    }
});

// Save structure
function saveStructure() {
    const items = document.querySelectorAll('.curriculum-item');
    const structure = [];
    
    items.forEach((item, index) => {
        structure.push({
            id: parseInt(item.dataset.id),
            type: item.dataset.type,
            sort_order: index
        });
    });
    
    fetch(`/courses/builder/${currentCourseId}/structure`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ structure: structure })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Course structure saved successfully!');
        }
    })
    .catch(error => {
        console.error('Error saving structure:', error);
    });
}

// Update item
function updateItem(id, type) {
    const item = document.querySelector(`[data-id="${id}"][data-type="${type}"]`);
    const titleInput = item.querySelector('.item-title');
    const title = titleInput.value.trim();
    
    fetch(`/courses/builder/${currentCourseId}/${type}/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            title: title,
            is_published: item.querySelector('.item-actions').dataset.published === 'true'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Item updated successfully!');
        }
    })
    .catch(error => {
        console.error('Error updating item:', error);
    });
}

// Add lesson
function addLesson() {
    fetch(`/courses/builder/${currentCourseId}/lesson`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            title: 'New Lesson',
            type: 'video',
            duration_minutes: 0,
            is_free: false,
            is_published: false
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show new item
        }
    })
    .catch(error => {
        console.error('Error adding lesson:', error);
    });
}

// Add quiz
function addQuiz() {
    fetch(`/courses/builder/${currentCourseId}/quiz`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            title: 'New Quiz',
            time_limit_minutes: 60,
            passing_score: 70,
            max_attempts: 3,
            is_published: false
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show new item
        }
    })
    .catch(error => {
        console.error('Error adding quiz:', error);
    });
}

// Edit item
function editItem(id, type) {
    const item = document.querySelector(`[data-id="${id}"][data-type="${type}"]`);
    const titleInput = item.querySelector('.item-title');
    
    // Make title editable
    titleInput.focus();
    titleInput.select();
}

// Delete item
function deleteItem(id, type) {
    deleteItemType = type;
    deleteItemId = id;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    deleteItemType = null;
    deleteItemId = null;
}

function confirmDelete() {
    if (deleteItemId && deleteItemType) {
        fetch(`/courses/builder/${currentCourseId}/${deleteItemType}/${deleteItemId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeDeleteModal();
                location.reload(); // Reload to show updated list
            }
        })
        .catch(error => {
            console.error('Error deleting item:', error);
        });
    }
}

// Show success message
function showSuccessMessage(message) {
    const successDiv = document.getElementById('success-message');
    const successText = document.getElementById('success-text');
    
    successText.textContent = message;
    successDiv.classList.remove('hidden');
    
    setTimeout(() => {
        successDiv.classList.add('hidden');
    }, 3000);
}
</script>

<style>
.course-builder {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.builder-header {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-left {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.course-structure {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.structure-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.structure-actions {
    display: flex;
    gap: 8px;
}

.curriculum-items {
    min-height: 200px;
}

.curriculum-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.curriculum-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.curriculum-item.lesson {
    border-left: 4px solid #3b82f6;
}

.curriculum-item.quiz {
    border-left: 4px solid #8b5cf6;
}

.item-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 12px;
    cursor: move;
}

.drag-handle {
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}

.item-icon {
    color: #6b7280;
    font-size: 20px;
}

.item-content {
    flex: 1;
}

.item-title {
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 500;
    color: #1f2937;
    outline: none;
    width: 100%;
}

.item-title:focus {
    background: white;
    border-radius: 4px;
    padding: 4px 8px;
}

.item-meta {
    display: flex;
    gap: 8px;
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

.duration {
    background: #e5e7eb;
    padding: 2px 6px;
    border-radius: 12px;
}

.badge {
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.badge-success {
    background: #10b981;
    color: white;
}

.badge-info {
    background: #3b82f6;
    color: white;
}

.item-actions {
    display: flex;
    gap: 4px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    color: #9ca3af;
    margin-bottom: 16px;
}

.empty-actions {
    margin-top: 20px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    color: #374151;
    border-color: #9ca3af;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.material-icons {
    font-size: 18px;
}
</style>
@endsection
