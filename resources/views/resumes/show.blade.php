@extends('layouts.app')

@section('title', $resume->name . ' - Resume')

@push('styles')
    <style>
        .download-btn {
            @apply inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition;
        }

        .download-btn.filtered {
            @apply border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100;
        }

        .filter-badge {
            @apply ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700;
        }

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

        .cert-badge {
            @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mr-2 mb-1;
        }

        .award-meta {
            @apply text-xs text-gray-500;
        }

        .pub-meta {
            @apply text-xs text-gray-500 italic;
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

                <!-- 🔹 Download Filtered JSON Button 🔹 -->
                <a href="{{ route('resumes.json', $resume) }}{{ !empty($filter_keywords) ? '?' . http_build_query(array_filter(['keywords' => $filter_keywords, 'match_all' => $filter_match_all ?? null])) : '' }}"
                    class="download-btn {{ !empty($filter_keywords) ? 'filtered' : '' }}"
                    title="Download {{ !empty($filter_keywords) ? 'filtered ' : '' }}resume as JSON">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download JSON
                    @if (!empty($filter_keywords))
                        <span
                            class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">filtered</span>
                    @endif
                </a>

                <!-- 🔹 Download Filtered Latex Button 🔹 -->
                <a href="{{ route('resumes.latex', $resume) }}{{ !empty($filter_keywords) ? '?' . http_build_query(array_filter(['keywords' => $filter_keywords, 'match_all' => $filter_match_all ?? null])) : '' }}"
                    class="download-btn {{ !empty($filter_keywords) ? 'filtered' : '' }}"
                    title="Download {{ !empty($filter_keywords) ? 'filtered ' : '' }}resume as LaTeX">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download LaTeX
                    @if (!empty($filter_keywords))
                        <span
                            class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">filtered</span>
                    @endif
                </a>

                <!-- 🔹 Download Filtered PDF Button 🔹 -->
                <a href="{{ route('resumes.pdf', $resume) }}{{ !empty($filter_keywords) ? '?' . http_build_query(array_filter(['keywords' => $filter_keywords, 'match_all' => $filter_match_all ?? null])) : '' }}"
                    class="download-btn {{ !empty($filter_keywords) ? 'filtered' : '' }}"
                    title="Download {{ !empty($filter_keywords) ? 'filtered ' : '' }}resume as PDF">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download PDF
                    @if (!empty($filter_keywords))
                        <span
                            class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">filtered</span>
                    @endif
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

            <!-- Keyword Filter Form 🔹 -->
            @if (!empty($filter_keywords))
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

            <!-- Filter Form -->
            <div class="mb-6">
                <form method="GET" action="{{ route('resumes.show', $resume) }}"
                    class="flex flex-wrap gap-3 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <label for="keywords" class="sr-only">Filter by keywords</label>
                        <input type="text" name="keywords" id="keywords"
                            value="{{ is_array($filter_keywords ?? []) ? implode(',', $filter_keywords ?? []) : $filter_keywords ?? '' }}"
                            placeholder="Filter: php, laravel, api..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="match_all" id="match_all" value="1"
                            {{ $filter_match_all ?? false ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="match_all" class="text-sm text-gray-700">Match ALL</label>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Filter
                    </button>

                    @if (!empty($filter_keywords))
                        <a href="{{ route('resumes.show', $resume) }}"
                            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900">
                            Reset
                        </a>
                    @endif
                </form>

                <!-- Filter Stats -->
                @if (!empty($filter_metadata))
                    <div class="mt-2 text-xs text-gray-500 flex flex-wrap gap-4">
                        <span>Work:
                            {{ $filter_metadata['filtered_counts']['work'] ?? 0 }}/{{ $filter_metadata['original_counts']['work'] ?? 0 }}</span>
                        <span>Skills:
                            {{ $filter_metadata['filtered_counts']['skills'] ?? 0 }}/{{ $filter_metadata['original_counts']['skills'] ?? 0 }}</span>
                        <span>Education:
                            {{ $filter_metadata['filtered_counts']['education'] ?? 0 }}/{{ $filter_metadata['original_counts']['education'] ?? 0 }}</span>
                    </div>
                @endif
            </div>

            <!-- Resume Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <!-- Header: Name & Contact (always shown) -->
                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $resume->name }}</h1>

                        @php $basics = $parsed_data['basics'] ?? $resume->getBasicsAttribute(); @endphp

                        @if (!empty($basics['email']) || !empty($basics['phone']))
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                @if (!empty($basics['email']))
                                    <a href="mailto:{{ $basics['email'] }}" class="hover:text-indigo-600">📧
                                        {{ $basics['email'] }}</a>
                                @endif
                                @if (!empty($basics['phone']))
                                    <span>📱 {{ $basics['phone'] }}</span>
                                @endif
                                @if (!empty($basics['location']))
                                    <span>📍
                                        {{ $basics['location']['city'] ?? '' }}{{ !empty($basics['location']['countryCode']) ? ', ' . $basics['location']['countryCode'] : '' }}</span>
                                @endif
                            </div>
                        @endif

                        @if (!empty($basics['summary']))
                            <p class="mt-4 text-gray-700">{{ $basics['summary'] }}</p>
                        @endif

                        <!-- Links / Profiles -->
                        @if (!empty($basics['links']) && is_array($basics['links']))
                            <div class="mt-3 flex flex-wrap gap-3">
                                @foreach ($basics['links'] as $link)
                                    @if (!empty($link['url']))
                                        <a href="{!! $link['url'] !!}" target="_blank" rel="noopener noreferrer"
                                            class="text-sm text-indigo-600 hover:underline">
                                            {!! $link['label'] ?? $link['url'] !!}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- 🔹 Skills Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['skills']) && is_array($parsed_data['skills']) && !empty($parsed_data['skills']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Skills</h2>
                            <div class="flex flex-wrap">
                                @foreach ($parsed_data['skills'] as $skill)
                                    @if (!empty($skill['keywords']))
                                        @foreach ($skill['keywords'] as $keyword)
                                            <span class="tag">{{ $keyword }}</span>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 🔹 Work Experience Section (safe conditional + cross-refs) 🔹 -->
                    @if (isset($parsed_data['work']) && is_array($parsed_data['work']) && !empty($parsed_data['work']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Work Experience</h2>

                            @php
                                // Build project lookup map for cross-references
                                $projectMap = [];
                                $projects = $parsed_data['projects'] ?? [];
                                foreach ($projects as $proj) {
                                    if (!empty($proj['id'])) {
                                        $projectMap[$proj['id']] = $proj;
                                    }
                                    if (!empty($proj['name'])) {
                                        $projectMap[$proj['name']] = $proj;
                                    }
                                }
                            @endphp

                            @foreach ($parsed_data['work'] as $job)
                                <div class="resume-item">
                                    <div class="resume-item-header">{{ $job['position'] ?? 'Unknown Position' }}</div>
                                    <div class="resume-item-meta">
                                        {{ $job['name'] ?? ($job['employer'] ?? 'Company') }}
                                        @if (!empty($job['location']))
                                            • {{ $job['location'] }}
                                        @endif
                                        @if (!empty($job['startDate']))
                                            • {{ $job['startDate'] }} @if (!empty($job['endDate']))
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

                                    <!-- Cross-Referenced Projects -->
                                    @if (!empty($job['crossReferencedProjects']) && is_array($job['crossReferencedProjects']))
                                        <div class="mt-2">
                                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Related
                                                Projects:</span>
                                            <div class="mt-1 flex flex-wrap">
                                                @foreach ($job['crossReferencedProjects'] as $ref)
                                                    @php
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

                    <!-- 🔹 Education Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['education']) && is_array($parsed_data['education']) && !empty($parsed_data['education']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Education</h2>
                            @foreach ($parsed_data['education'] as $edu)
                                <div class="resume-item">
                                    <div class="resume-item-header">
                                        {{ $edu['studyType'] ?? '' }}
                                        @if (!empty($edu['area']))
                                            in {{ $edu['area'] }}
                                        @endif
                                    </div>
                                    <div class="resume-item-meta">
                                        {{ $edu['institution'] ?? 'Institution' }}
                                        @if (!empty($edu['startDate']))
                                            • {{ $edu['startDate'] }} @if (!empty($edu['endDate']))
                                                - {{ $edu['endDate'] }}
                                            @endif
                                        @endif
                                    </div>
                                    @if (!empty($edu['score']))
                                        <div class="text-sm text-gray-600">GPA/Score: {{ $edu['score'] }}</div>
                                    @endif
                                    @if (!empty($edu['courses']))
                                        <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                            @foreach ($edu['courses'] as $course)
                                                <li>{{ $course }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- 🔹 Volunteer Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['volunteer']) && is_array($parsed_data['volunteer']) && !empty($parsed_data['volunteer']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Volunteer Experience</h2>
                            @foreach ($parsed_data['volunteer'] as $item)
                                <div class="resume-item">
                                    <div class="resume-item-header">
                                        {{ $item['position'] ?? ($item['role'] ?? 'Volunteer') }}
                                    </div>
                                    <div class="resume-item-meta">
                                        {{ $item['organization'] ?? '' }}
                                        @if (!empty($item['location']))
                                            • {{ $item['location'] }}
                                        @endif
                                        @if (!empty($item['startDate']))
                                            • {{ $item['startDate'] }} @if (!empty($item['endDate']))
                                                - {{ $item['endDate'] }}
                                            @else
                                                - Present
                                            @endif
                                        @endif
                                    </div>
                                    @if (!empty($item['summary']))
                                        <div class="resume-item-body">{{ $item['summary'] }}</div>
                                    @endif
                                    @if (!empty($item['highlights']))
                                        <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
                                            @foreach ($item['highlights'] as $highlight)
                                                <li>{{ $highlight }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- 🔹 Certifications Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['certifications']) &&
                            is_array($parsed_data['certifications']) &&
                            !empty($parsed_data['certifications']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Certifications</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($parsed_data['certifications'] as $cert)
                                    <div class="resume-item">
                                        <div class="resume-item-header">{{ $cert['name'] ?? 'Certification' }}</div>
                                        @if (!empty($cert['issuer']))
                                            <div class="resume-item-meta">{{ $cert['issuer'] }}</div>
                                        @endif
                                        @if (!empty($cert['date']))
                                            <div class="resume-item-meta">Issued: {{ $cert['date'] }}</div>
                                        @endif
                                        @if (!empty($cert['url']))
                                            <a href="{{ $cert['url'] }}" target="_blank"
                                                class="text-sm text-indigo-600 hover:underline mt-1 inline-block">
                                                View Certificate →
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 🔹 Publications Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['publications']) &&
                            is_array($parsed_data['publications']) &&
                            !empty($parsed_data['publications']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Publications</h2>
                            @foreach ($parsed_data['publications'] as $pub)
                                <div class="resume-item">
                                    <div class="resume-item-header">
                                        @if (!empty($pub['url']))
                                            <a href="{{ $pub['url'] }}" target="_blank"
                                                class="text-indigo-600 hover:underline">
                                                {{ $pub['name'] ?? 'Untitled Publication' }}
                                            </a>
                                        @else
                                            {{ $pub['name'] ?? 'Untitled Publication' }}
                                        @endif
                                    </div>
                                    @if (!empty($pub['publisher']))
                                        <div class="resume-item-meta pub-meta">Publisher: {{ $pub['publisher'] }}</div>
                                    @endif
                                    @if (!empty($pub['releaseDate']))
                                        <div class="resume-item-meta pub-meta">Published: {{ $pub['releaseDate'] }}</div>
                                    @endif
                                    @if (!empty($pub['summary']))
                                        <div class="resume-item-body mt-1">{{ $pub['summary'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- 🔹 Awards Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['awards']) && is_array($parsed_data['awards']) && !empty($parsed_data['awards']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Awards & Honors</h2>
                            @foreach ($parsed_data['awards'] as $award)
                                <div class="resume-item">
                                    <div class="resume-item-header">{{ $award['title'] ?? 'Award' }}</div>
                                    @if (!empty($award['awarder']))
                                        <div class="resume-item-meta">From: {{ $award['awarder'] }}</div>
                                    @endif
                                    @if (!empty($award['date']))
                                        <div class="resume-item-meta award-meta">Date: {{ $award['date'] }}</div>
                                    @endif
                                    @if (!empty($award['summary']))
                                        <div class="resume-item-body">{{ $award['summary'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- 🔹 Languages Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['languages']) && is_array($parsed_data['languages']) && !empty($parsed_data['languages']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Languages</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($parsed_data['languages'] as $lang)
                                    <span class="tag">
                                        {{ $lang['language'] ?? 'Unknown' }}
                                        @if (!empty($lang['fluency']))
                                            <span class="ml-1 text-xs opacity-75">({{ $lang['fluency'] }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 🔹 Interests Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['interests']) && is_array($parsed_data['interests']) && !empty($parsed_data['interests']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">Interests</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($parsed_data['interests'] as $interest)
                                    <div class="resume-item">
                                        <div class="resume-item-header">{{ $interest['name'] ?? 'Interest' }}</div>
                                        @if (!empty($interest['keywords']) && is_array($interest['keywords']))
                                            <div class="mt-1 flex flex-wrap">
                                                @foreach ($interest['keywords'] as $kw)
                                                    <span class="tag">{{ $kw }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 🔹 References Section (safe conditional) 🔹 -->
                    @if (isset($parsed_data['references']) && is_array($parsed_data['references']) && !empty($parsed_data['references']))
                        <div class="resume-section">
                            <h2 class="resume-section-title">References</h2>
                            @foreach ($parsed_data['references'] as $ref)
                                <div class="resume-item">
                                    <div class="resume-item-header">{{ $ref['name'] ?? 'Reference' }}</div>
                                    @if (!empty($ref['reference']))
                                        <div class="resume-item-body mt-1">{{ $ref['reference'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- No Results Message (when filter yields nothing) -->
                    @if (
                        !empty($filter_keywords) &&
                            empty($parsed_data['skills']) &&
                            empty($parsed_data['work']) &&
                            empty($parsed_data['education']) &&
                            empty($parsed_data['volunteer']) &&
                            empty($parsed_data['certifications']) &&
                            empty($parsed_data['publications']) &&
                            empty($parsed_data['awards']) &&
                            empty($parsed_data['languages']) &&
                            empty($parsed_data['interests']) &&
                            empty($parsed_data['references']))
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-lg mb-2">😕 No results match your filter</p>
                            <p class="text-sm">Try adjusting your keywords or clearing the filter to see all content.</p>
                            <a href="{{ route('resumes.show', $resume) }}"
                                class="mt-4 inline-block text-indigo-600 hover:underline">
                                Clear filter →
                            </a>
                        </div>
                    @endif

                    <!-- Meta Info (always shown) -->
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
