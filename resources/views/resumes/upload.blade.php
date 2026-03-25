@extends('layouts.app')

@section('title', 'Upload Resume')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="list-disc list-inside text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Upload JSON Resume</h2>
                
                <p class="text-gray-600 mb-6">
                    Upload a resume in <a href="https://jsonresume.org/schema/" target="_blank" class="text-indigo-600 hover:underline">JSON Resume format</a>. 
                    File must be valid JSON with a <code>basics</code> section. Max size: 2MB.
                </p>

                <form action="{{ route('resumes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="resume_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Resume File (.json)
                        </label>
                        <input type="file" 
                               name="resume_file" 
                               id="resume_file" 
                               accept=".json,application/json"
                               required
                               class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100
                                      border border-gray-300 rounded-md
                                      focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('resume_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('dashboard') }}" 
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Upload Resume
                        </button>
                    </div>
                </form>

                <!-- Sample JSON Resume Structure -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Expected JSON Structure</h3>
                    <pre class="bg-gray-50 p-4 rounded-md text-xs text-gray-700 overflow-x-auto"><code>{
  "$schema": "https://raw.githubusercontent.com/jsonresume/resume-schema/v1.0.0/schema.json",
  "basics": {
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "(555) 123-4567",
    "summary": "A brief summary...",
    "location": { "city": "San Francisco", "countryCode": "US" }
  },
  "work": [],
  "education": [],
  "skills": []
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection