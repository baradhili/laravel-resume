@extends('layouts.app')

@section('title', $resume->name . ' - Resume')

@push('styles')
    <style>
        .resume-section {
            @apply mb-6;
        }

        .resume-section-title {
            @apply text-lg font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-200;
        }

        .resume-item {
            @apply mb-4 pl-4 border-l-2 border-gray-200;
        }

        .resume-item-header {
            @apply font-medium text-gray-900;
        }

        .resume-item-meta {
            @apply text-sm text-gray-500 mb-1;
        }

        .resume-item-body {
            @apply text-gray-700;
        }

        .tag {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mr-2 mb-2;
        }

        .project-ref {
            @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 mr-2 mb-1 hover:bg-gray-200 transition;
        }

        .project-ref-linked {
            @apply bg-indigo-50 text-indigo-700 hover:bg-indigo-100;
        }

        .project-ref-external {
            @apply bg-amber-50 text-amber-700 hover:bg-amber-100;
        }
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
                    <form action="{{ route('resumes.destroy', $resume) }}" method="POST" class="inline"
                        onsubmit="return confirm('Delete this resume?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- 🔹 Keyword Filter Form 🔹 -->
            @if ($filter_keywords)
                <div class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-indigo-800">🔍 Filtering by:</span>
                        <span class="ml-2 text-sm text-indigo-700">
                            @if (is_array($filter_keywords))
                                {{ implode(', ', $filter_keywords) }}
                            @else
                                {{ $filter_keywords }}
                            @endif
                            @if ($filter_match_all)
                                <span class="ml-2 px-2 py-0.5 rounded text-xs bg-indigo-200 text-indigo-800">ALL</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-700">ANY</span>
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('resumes.show', $resume) }}"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Clear filter →
                    </a>
                </div>
            @endif

            <!-- Filter Toggle/Form (can be expanded to a modal or sidebar) -->
            <div class="mb-6">
                <form method="GET" action="{{ route('resumes.show', $resume) }}"
                    class="flex flex-wrap gap-3 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <label for="keywords" class="sr-only">Filter by keywords</label>
                        <input type="text" name="keywords" id="keywords"
                            value="{{ is_array($filter_keywords) ? implode(',', $filter_keywords) : $filter_keywords ?? '' }}"
                            placeholder="Filter: php, laravel, api..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="match_all" id="match_all" value="1"
                            {{ $filter_match_all ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="match_all" class="text-sm text-gray-700">Match ALL</label>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Filter
                    </button>

                    @if ($filter_keywords)
                        <a href="{{ route('resumes.show', $resume) }}"
                            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900">
                            Reset
                        </a>
                    @endif
                </form>

                <!-- Filter Stats (if metadata available) -->
                @if ($filter_metadata)
                    <div class="mt-2 text-xs text-gray-500 flex flex-wrap gap-4">
                        <span>Work:
                            {{ $filter_metadata['filtered_counts']['work'] }}/{{ $filter_metadata['original_counts']['work'] }}</span>
                        <span>Projects:
                            {{ $filter_metadata['filtered_counts']['projects'] }}/{{ $filter_metadata['original_counts']['projects'] }}</span>
                        <span>Skills:
                            {{ $filter_metadata['filtered_counts']['skills'] }}/{{ $filter_metadata['original_counts']['skills'] }}</span>
                    </div>
                @endif
            </div>

            <!-- Resume Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <!-- Header: Name & Contact -->
                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $resume->name }}</h1>

                        @if ($resume->email || $resume->getBasicsAttribute()['phone'] ?? null)
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                @if ($resume->email)
                                    <a href="mailto:{{ $resume->email }}" class="hover:text-indigo-600">📧
                                        {{ $resume->email }}</a>
                                @endif
                                @if ($resume->getBasicsAttribute()['phone'] ?? null)
                                    <span>📱 {{ $resume->getBasicsAttribute()['phone'] }}</span>
                                @endif
                                @if ($resume->getBasicsAttribute()['location'] ?? null)
                                    <span>📍
                                        {{ $resume->getBasicsAttribute()['location']['city'] ?? '' }}{{ $resume->getBasicsAttribute()['location']['countryCode'] ? ', ' . $resume->getBasicsAttribute()['location']['countryCode'] : '' }}</span>
                                @endif
                            </div>
                        @endif

                        @if ($resume->getBasicsAttribute()['summary'] ?? null)
                            <p class="mt-4 text-gray-700">{{ $resume->getBasicsAttribute()['summary'] }}</p>
                        @endif
                    </div>

                    <!-- Skills -->
                    @if(!empty($parsed_data['skills'] ?? []) && is_array($parsed_data['skills'] ?? []))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Skills</h2>
                            <div class="flex flex-wrap">
                                @foreach ($parsed_data['skills'] ?? [] as $skill)
                                    @if (!empty($skill['keywords']))
                                        @foreach ($skill['keywords'] as $keyword)
                                            <span class="tag">{{ $keyword }}</span>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 🔹 Work Experience with Cross-Referenced Projects 🔹 -->
                    @if (!empty($resume->parsed_data['work']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Work Experience</h2>

                            @php
                                // Build project lookup map: id => project, name => project (fallback)
                                $projectMap = [];
                                $projects = $resume->parsed_data['projects'] ?? [];
                                foreach ($projects as $proj) {
                                    if (!empty($proj['id'])) {
                                        $projectMap[$proj['id']] = $proj;
                                    }
                                    if (!empty($proj['name'])) {
                                        $projectMap[$proj['name']] = $proj;
                                    }
                                }
                            @endphp

                            @foreach ($parsed_data['work'] ?? [] as $job)
                                <div class="resume-item">
                                    <div class="resume-item-header">{{ $job['position'] ?? 'Unknown Position' }}</div>
                                    <div class="resume-item-meta">
                                        {{ $job['employer'] ?? 'Company' }}
                                        @if (isset($job['startDate']))
                                            • {{ $job['startDate'] }} @if (isset($job['endDate']))
                                                - {{ $job['endDate'] }}
                                            @else
                                                - Present
                                            @endif
                                        @endif
                                    </div>
                                    @if (!empty($job['summary']))
                                        <div class="resume-item-body">{{ $job['summary'] }}</div>
                                    @endif

                                    @if (!empty($job['highlights']))
                                        <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                            @foreach ($job['highlights'] as $highlight)
                                                <li>{{ $highlight }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <!-- 🔹 Cross-Referenced Projects 🔹 -->
                                    @if (!empty($job['crossReferencedProjects']) && is_array($job['crossReferencedProjects']))
                                        <div class="mt-2">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Related
                                                Projects:</span>
                                            <div class="mt-1 flex flex-wrap">
                                                @foreach ($job['crossReferencedProjects'] as $ref)
                                                    @php
                                                        // Try to find project by id or name
                                                        $project = $projectMap[$ref] ?? null;
                                                        $displayName = $project['name'] ?? $ref;
                                                        $projectUrl = $project['url'] ?? null;
                                                        $isFound = $project !== null;
                                                    @endphp

                                                    @if ($projectUrl)
                                                        <a href="{{ $projectUrl }}" target="_blank"
                                                            class="project-ref {{ $isFound ? 'project-ref-linked' : 'project-ref-external' }}">
                                                            🔗 {{ $displayName }}
                                                        </a>
                                                    @else
                                                        <span
                                                            class="project-ref {{ $isFound ? 'project-ref-linked' : 'project-ref-external' }}">
                                                            {{ $displayName }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Education -->
                    @if(isset($parsed_data['education']) && is_array($parsed_data['education']) && !empty($parsed_data['education']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Education</h2>
                            @foreach($parsed_data['education'] ?? [] as $edu)
                                <div class="resume-item">
                                    <div class="resume-item-header">{{ $edu['studyType'] ?? '' }} in
                                        {{ $edu['area'] ?? '' }}</div>
                                    <div class="resume-item-meta">
                                        {{ $edu['institution'] ?? 'Institution' }}
                                        @if (isset($edu['startDate']))
                                            • {{ $edu['startDate'] }} @if (isset($edu['endDate']))
                                                - {{ $edu['endDate'] }}
                                            @endif
                                        @endif
                                    </div>
                                    @if (!empty($edu['score']))
                                        <div class="text-sm text-gray-600">GPA: {{ $edu['score'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Meta Info -->
                    <div class="mt-8 pt-4 border-t border-gray-200 text-xs text-gray-500">
                        <p>Uploaded: {{ $resume->uploaded_at->format('F j, Y \a\t g:i A') }}</p>
                        @if ($resume->json_resume_version)
                            <p>Schema: {{ $resume->json_resume_version }}</p>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
