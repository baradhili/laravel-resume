@extends('layouts.app')

@section('title', $resume->name . ' - Resume')

@push('styles')
<style>
    .resume-section { @apply mb-6; }
    .resume-section-title { @apply text-lg font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-200; }
    .resume-item { @apply mb-4 pl-4 border-l-2 border-gray-200; }
    .resume-item-header { @apply font-medium text-gray-900; }
    .resume-item-meta { @apply text-sm text-gray-500 mb-1; }
    .resume-item-body { @apply text-gray-700; }
    .tag { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mr-2 mb-2; }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Actions -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('resumes.index') }}" 
               class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                ← Back to Resumes
            </a>
            <div class="space-x-3">
                <a href="{{ route('resumes.upload') }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Upload Another
                </a>
                <form action="{{ route('resumes.destroy', $resume) }}" method="POST" class="inline" onsubmit="return confirm('Delete this resume?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Resume Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                
                <!-- Header: Name & Contact -->
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $resume->name }}</h1>
                    
                    @if($resume->email || $resume->getBasicsAttribute()['phone'] ?? null)
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            @if($resume->email)
                                <a href="mailto:{{ $resume->email }}" class="hover:text-indigo-600">📧 {{ $resume->email }}</a>
                            @endif
                            @if($resume->getBasicsAttribute()['phone'] ?? null)
                                <span>📱 {{ $resume->getBasicsAttribute()['phone'] }}</span>
                            @endif
                            @if($resume->getBasicsAttribute()['location'] ?? null)
                                <span>📍 {{ $resume->getBasicsAttribute()['location']['city'] ?? '' }}{{ $resume->getBasicsAttribute()['location']['countryCode'] ? ', ' . $resume->getBasicsAttribute()['location']['countryCode'] : '' }}</span>
                            @endif
                        </div>
                    @endif

                    @if($resume->getBasicsAttribute()['summary'] ?? null)
                        <p class="mt-4 text-gray-700">{{ $resume->getBasicsAttribute()['summary'] }}</p>
                    @endif
                </div>

                <!-- Skills -->
                @if(!empty($resume->parsed_data['skills']))
                    <div class="resume-section">
                        <h2 class="resume-section-title">Skills</h2>
                        <div class="flex flex-wrap">
                            @foreach($resume->parsed_data['skills'] as $skill)
                                @if(!empty($skill['keywords']))
                                    @foreach($skill['keywords'] as $keyword)
                                        <span class="tag">{{ $keyword }}</span>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Work Experience -->
                @if(!empty($resume->parsed_data['work']))
                    <div class="resume-section">
                        <h2 class="resume-section-title">Work Experience</h2>
                        @foreach($resume->parsed_data['work'] as $job)
                            <div class="resume-item">
                                <div class="resume-item-header">{{ $job['position'] ?? 'Unknown Position' }}</div>
                                <div class="resume-item-meta">
                                    {{ $job['name'] ?? 'Company' }} 
                                    @if(isset($job['startDate']))
                                        • {{ $job['startDate'] }} @if(isset($job['endDate'])) - {{ $job['endDate'] }} @else - Present @endif
                                    @endif
                                </div>
                                @if(!empty($job['summary']))
                                    <div class="resume-item-body">{{ $job['summary'] }}</div>
                                @endif
                                @if(!empty($job['highlights']))
                                    <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                        @foreach($job['highlights'] as $highlight)
                                            <li>{{ $highlight }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Education -->
                @if(!empty($resume->parsed_data['education']))
                    <div class="resume-section">
                        <h2 class="resume-section-title">Education</h2>
                        @foreach($resume->parsed_data['education'] as $edu)
                            <div class="resume-item">
                                <div class="resume-item-header">{{ $edu['studyType'] ?? '' }} in {{ $edu['area'] ?? '' }}</div>
                                <div class="resume-item-meta">
                                    {{ $edu['institution'] ?? 'Institution' }}
                                    @if(isset($edu['startDate']))
                                        • {{ $edu['startDate'] }} @if(isset($edu['endDate'])) - {{ $edu['endDate'] }} @endif
                                    @endif
                                </div>
                                @if(!empty($edu['score']))
                                    <div class="text-sm text-gray-600">GPA: {{ $edu['score'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Projects -->
                @if(!empty($resume->parsed_data['projects']))
                    <div class="resume-section">
                        <h2 class="resume-section-title">Projects</h2>
                        @foreach($resume->parsed_data['projects'] as $project)
                            <div class="resume-item">
                                <div class="resume-item-header">
                                    @if(!empty($project['url']))
                                        <a href="{{ $project['url'] }}" target="_blank" class="text-indigo-600 hover:underline">{{ $project['name'] ?? 'Untitled Project' }}</a>
                                    @else
                                        {{ $project['name'] ?? 'Untitled Project' }}
                                    @endif
                                </div>
                                @if(!empty($project['summary']))
                                    <div class="resume-item-body">{{ $project['summary'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Meta Info -->
                <div class="mt-8 pt-4 border-t border-gray-200 text-xs text-gray-500">
                    <p>Uploaded: {{ $resume->uploaded_at->format('F j, Y \a\t g:i A') }}</p>
                    @if($resume->json_resume_version)
                        <p>Schema: {{ $resume->json_resume_version }}</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
@endsection