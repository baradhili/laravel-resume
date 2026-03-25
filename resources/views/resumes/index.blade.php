@extends('layouts.app')

@section('title', 'My Resumes')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">My Resumes</h2>
            <a href="{{ route('resumes.upload') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                + Upload New
            </a>
        </div>

        @if($resumes->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-500">
                    <p class="mb-4">No resumes uploaded yet.</p>
                    <a href="{{ route('resumes.upload') }}" class="text-indigo-600 hover:underline">Upload your first resume →</a>
                </div>
            </div>
        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-200">
                    @foreach($resumes as $resume)
                        <li class="p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('resumes.show', $resume) }}" class="block">
                                        <p class="text-lg font-medium text-gray-900 truncate">
                                            {{ $resume->name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $resume->original_filename }} • 
                                            Uploaded {{ $resume->uploaded_at->diffForHumans() }}
                                        </p>
                                    </a>
                                </div>
                                <div class="ml-4 flex-shrink-0 flex space-x-3">
                                    <a href="{{ route('resumes.show', $resume) }}" 
                                       class="text-sm text-indigo-600 hover:text-indigo-900">
                                        View
                                    </a>
                                    <form action="{{ route('resumes.destroy', $resume) }}" method="POST" onsubmit="return confirm('Delete this resume?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $resumes->links() }}
            </div>
        @endif

    </div>
</div>
@endsection