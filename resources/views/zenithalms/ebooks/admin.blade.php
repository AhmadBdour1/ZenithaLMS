@extends('zenithalms.layouts.app')

@section('title', 'Ebooks Management - ZenithaLMS')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-teal-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">Ebooks Management</h1>
                <p class="text-xl text-green-100">Manage all ebooks in the system</p>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">All Ebooks</h2>
                <div class="flex space-x-4">
                    <a href="{{ route('zenithalms.ebooks.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <span class="material-icons-round text-sm mr-2">add</span>
                        Create Ebook
                    </a>
                </div>
            </div>

            @if($ebooks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($ebooks as $ebook)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($ebook->thumbnail)
                                                <img src="{{ $ebook->thumbnail }}" alt="{{ $ebook->title }}" class="h-10 w-10 rounded-full mr-3">
                                            @else
                                                <div class="h-10 w-10 bg-gray-200 rounded-full mr-3 flex items-center justify-center">
                                                    <span class="material-icons-round text-gray-400 text-sm">book</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $ebook->title }}</div>
                                                <div class="text-sm text-gray-500">{{ $ebook->description }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $ebook->user->name ?? 'Unknown' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $ebook->category->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">${{ number_format($ebook->price, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($ebook->status == 'published') bg-green-100 text-green-800
                                            @elseif($ebook->status == 'draft') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800 @endif
                                        ">
                                            {{ ucfirst($ebook->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('zenithalms.ebooks.admin.show', $ebook->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            <a href="{{ route('zenithalms.ebooks.admin.edit', $ebook->id) }}" class="text-green-600 hover:text-green-900">Edit</a>
                                            <form action="{{ route('zenithalms.ebooks.admin.destroy', $ebook->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $ebooks->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <span class="material-icons-round text-gray-400 text-2xl">book</span>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No ebooks available</h3>
                    <p class="text-gray-500">Get started by creating your first ebook.</p>
                    <div class="mt-6">
                        <a href="{{ route('zenithalms.ebooks.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <span class="material-icons-round text-sm mr-2">add</span>
                            Create First Ebook
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
