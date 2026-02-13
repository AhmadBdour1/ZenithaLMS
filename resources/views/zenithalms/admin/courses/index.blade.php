@extends('zenithalms.layouts.admin')

@section('title', 'Courses Management - ZenithaLMS Admin')

@section('content')
<!-- Page Header -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Courses Management</h1>
                <p class="text-neutral-600">Manage courses, content, and enrollments</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('zenithalms.admin.courses.create') }}" 
                   class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <span class="material-icons-round text-sm mr-2">add</span>
                    Create Course
                </a>
                <a href="{{ route('zenithalms.courses.import') }}" 
                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors">
                    <span class="material-icons-round text-sm mr-2">upload</span>
                    Import Courses
                </a>
                <a href="{{ route('zenithalms.courses.export') }}" 
                   class="px-4 py-2 border border-neutral-300 text-neutral-700 rounded-lg hover:border-primary-500 transition-colors">
                    <span class="material-icons-round text-sm mr-2">download</span>
                    Export Courses
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-wrap gap-4 mb-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-neutral-700">Search:</label>
                <input type="text" 
                       id="search-input"
                       placeholder="Search courses..." 
                       class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                       value="{{ request->get('search') }}">
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-neutral-700">Category:</label>
                <select id="category-filter" 
                        class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-neutral-700">Status:</label>
                <select id="status-filter" 
                        class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-neutral-700">Instructor:</label>
                <select id="instructor-filter" 
                        class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">All Instructors</option>
                    @foreach(\App\Models\User::whereHas('role', function($q) { $q->where('name', 'instructor'); })->get() as $instructor)
                        <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <button onclick="applyFilters()" 
                    class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                <span class="material-icons-round text-sm mr-2">filter_list</span>
                Apply Filters
            </button>
        </div>
    </div>
</div>

<!-- Courses Grid -->
<div class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="courses-grid">
            <!-- Courses will be loaded here via AJAX -->
        </div>
        
        <!-- Pagination -->
        <div id="pagination-container" class="mt-6">
            <!-- Pagination will be loaded here via AJAX -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ZenithaLMS: Courses Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
    
    // Search functionality
    document.getElementById('search-input').addEventListener('input', function(e) {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            loadCourses();
        }, 500);
    });
    
    // Filter functionality
    document.getElementById('category-filter').addEventListener('change', function(e) {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            loadCourses();
        }, 500);
    });
    
    document.getElementById('status-filter').addEventListener('change', function(e) {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            loadCourses();
        }, 500);
    });
    
    document.getElementById('instructor-filter').addEventListener('change', function(e) {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            loadCourses();
        }, 500);
    });
});

function loadCourses() {
    const search = document.getElementById('search-input').value;
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    const instructor = document.getElementById('instructor-filter').value;
    
    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    url.searchParams.set('category_id', category);
    url.searchParams.set('status', status);
    url.searchParams.set('instructor_id', instructor);
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            updateCoursesGrid(data.data);
            updatePagination(data.pagination);
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            showNotification('Error loading courses', 'error');
        });
}

function updateCoursesGrid(courses) {
    const grid = document.getElementById('courses-grid');
    grid.innerHTML = '';
    
    if (courses.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <span class="material-icons-round text-4xl text-neutral-400">school</span>
                <p class="text-neutral-500 mt-4">No courses found</p>
            </div>
        `;
        return;
    }
    
    courses.forEach(course => {
        const courseCard = document.createElement('div');
        courseCard.className = 'bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-all duration-300';
        courseCard.innerHTML = `
            <div class="relative h-48 bg-gradient-to-br from-primary-100 to-accent-purple/20">
                <img src="${ course.thumbnail || '/images/course-placeholder.jpg' }" 
                     alt="${ course.title }" 
                     class="w-full h-full object-cover">
                
                <!-- Status Badge -->
                <div class="absolute top-3 left-3">
                    <span class="px-3 py-1 bg-{{ 
                        course.is_published ? 'green' : 'yellow' 
                    }}-100 text-white text-xs font-semibold rounded-full">
                        ${ course.is_published ? 'Published' : 'Draft' }
                    </span>
                </div>
                
                <!-- Price Badge -->
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 bg-{{ 
                        course.is_free ? 'green' : 'blue' 
                    }}-100 text-white text-xs font-semibold rounded-full">
                        ${ course.is_free ? 'Free' : '$' + course.price }
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="font-bold text-lg mb-2">${ course.title }</h3>
                <p class="text-sm text-neutral-600 mb-4 line-clamp-2">${ course.description }</p>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                        <span>Instructor</span>
                        <span>${ course.instructor.name }</span>
                    </div>
                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                        <span>Category</span>
                        <span>${ course.category.name }</span>
                    </div>
                    <div class="flex justify-between text-sm text-neutral-600 mb-1">
                        <span>Enrollments</span>
                        <span>${ course.enrollments_count }</span>
                    </div>
                    <div class="flex justify-between text-sm text-neutral-600">
                        <span>Duration</span>
                        <span>${ course.duration || 'N/A' }</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('zenithalms.admin.courses.edit', course.id) }}" 
                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                        <span class="material-icons-round text-sm">edit</span>
                        Edit
                    </a>
                    <a href="{{ route('zenithalms.courses.show', course.slug) }}" 
                       class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg hover:border-primary-500 transition-colors text-center text-sm">
                        <span class="material-icons-round text-sm">visibility</span>
                        View
                    </a>
                    <button onclick="confirmDelete(${ course.id })" 
                            class="flex-1 px-3 py-2 border border-red-300 text-red-600 rounded-lg hover:border-red-500 transition-colors text-center text-sm">
                        <span class="material-icons-round text-sm">delete</span>
                        Delete
                    </button>
                </div>
            </div>
        `;
        
        grid.appendChild(courseCard);
    });
}

function updatePagination(pagination) {
    const container = document.getElementById('pagination-container');
    container.innerHTML = `
        <div class="flex justify-between items-center">
            <span class="text-sm text-neutral-600">
                Showing ${ pagination.from } to ${ pagination.to } of ${ pagination.total } courses
            </span>
            <div class="flex gap-2">
                ${ pagination.current_page > 1 ? `
                    <button onclick="loadCourses(${ pagination.current_page - 1 })" 
                            class="px-3 py-1 border border-neutral-300 rounded-md hover:border-primary-500 transition-colors">
                        <span class="material-icons-round text-sm">chevron_left</span>
                        Previous
                    </button>
                ` : '' }
                
                ${ pagination.current_page < pagination.last_page ? `
                    <button onclick="loadCourses(${ pagination.current_page + 1 })" 
                            class="px-3 py-1 border border-neutral-300 rounded-md hover:border-primary-500 transition-colors">
                        Next
                        <span class="material-icons-round text-sm">chevron_right</span>
                    </button>
                ` : '' }
            </div>
        </div>
    `;
}

function confirmDelete(courseId) {
    if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        fetch(`/zenithalms/admin/courses/${courseId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course deleted successfully', 'success');
                loadCourses();
            } else {
                showNotification('Failed to delete course', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting course:', error);
            showNotification('Error deleting course', 'error');
        });
    }
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
