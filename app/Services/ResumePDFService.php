<?php

namespace App\Services;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Techsemicolon\Latex;
use Techsemicolon\Exceptions\LatexException;

class ResumePDFService
{
    /**
     * Generate and download a PDF resume using Blade LaTeX template.
     *
     * @param Resume $resume The resume model
     * @param Request $request Current request (for filter params)
     * @param array $options Additional options
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws LatexException
     */
    public static function download(
        Resume $resume,
        Request $request,
        array $options = []
    ) {
        // Extract options with defaults
        $filenamePrefix = $options['filename_prefix'] ?? null;
        $binPath = $options['bin_path'] ?? config('services.latex.bin_path', '/usr/bin/lualatex');
        $includeMetadata = $options['include_metadata'] ?? true;

        // Get filter parameters
        $keywords = $request->query('keywords');
        $matchAll = $request->boolean('match_all', false);

        // Start with original parsed data
        $data = $resume->parsed_data;

        // Apply filtering if keywords provided
        if (!empty($keywords)) {
            $data = ResumeFilterService::filter($data, $keywords, $matchAll);
        }

        // Prepare data for Blade template
        $templateData = self::prepareTemplateData($data, $resume, $request, $includeMetadata);

        // Generate safe filename
        $baseName = $filenamePrefix ?? preg_replace('/[^a-z0-9]+/i', '-', strtolower($resume->name ?: 'resume'));
        $filename = $baseName . ($keywords ? '-filtered' : '') . '-' . now()->format('Y-m-d') . '.pdf';

        //define path to latex additional classes and styles
        $latexDir = storage_path('latex/');

        // Generate PDF using Blade view with hardcoded environment variables
        return (new Latex('latex.resume', $options['metadata'] ?? null))
            ->with($templateData)
            ->binPath($binPath)
            ->env([
                'HOME' => '/tmp',
                'PATH' => '/usr/local/bin:/usr/bin:/bin',
                'TEXMFVAR' => '/tmp/texmfv',
                'TEXINPUTS' => $latexDir . '://:',
            ])
            ->download($filename);
    }

    /**
     * Save PDF to storage using Blade LaTeX template.
     */
    public static function save(
        Resume $resume,
        Request $request,
        string $destinationPath,
        array $options = []
    ): string {
        $binPath = $options['bin_path'] ?? config('services.latex.bin_path', '/usr/bin/lualatex');
        $includeMetadata = $options['include_metadata'] ?? true;

        $keywords = $request->query('keywords');
        $matchAll = $request->boolean('match_all', false);
        $data = $resume->parsed_data;

        if (!empty($keywords)) {
            $data = ResumeFilterService::filter($data, $keywords, $matchAll);
        }

        $templateData = self::prepareTemplateData($data, $resume, $request, $includeMetadata);

        $baseName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($resume->name ?: 'resume'));
        $filename = $baseName . ($keywords ? '-filtered' : '') . '-' . now()->format('Y-m-d') . '.pdf';
        $fullPath = rtrim($destinationPath, '/') . '/' . $filename;

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        //define path to latex additional classes and styles
        $latexDir = storage_path('latex/');

        // Generate and save PDF with hardcoded environment variables
        (new Latex('latex.resume'))
            ->with($templateData)
            ->binPath($binPath)
            ->env([
                'HOME' => '/tmp',
                'PATH' => '/usr/local/bin:/usr/bin:/bin',
                'TEXMFVAR' => '/tmp/texmfv',
                'TEXINPUTS' => $latexDir . '://:',
            ])
            ->savePdf($fullPath);

        return $fullPath;
    }

    /**
     * Render raw LaTeX source from prepared template data.
     *
     * @param array $data Pre-filtered resume data (with metadata)
     * @param Resume $resume The resume model
     * @param Request $request Current request
     * @param array $options Additional options
     * @return string Rendered LaTeX source
     */
    public static function renderLatexSource(
        array $data,
        Resume $resume,
        Request $request,
        array $options = []
    ): string {
        $includeMetadata = $options['include_metadata'] ?? true;

        // Prepare data for Blade template (escaping, formatting, etc.)
        $templateData = self::prepareTemplateData($data, $resume, $request, $includeMetadata);

        // Render and return raw LaTeX source
        return view('latex.resume', $templateData)->render();
    }

    /**
     * Prepare data for Blade LaTeX template with escaped values.
     */
    protected static function prepareTemplateData(
        array $parsedData,
        Resume $resume,
        Request $request,
        bool $includeMetadata = true
    ): array {
        $basics = $parsedData['basics'] ?? [];
        $keywords = $request->query('keywords');

        $data = [
            // Core identity (escaped for LaTeX)
            'name' => self::escapeLatex($basics['name'] ?? $resume->name),
            'email' => self::escapeLatex($basics['email'] ?? ''),
            'phone' => self::escapeLatex($basics['phone'] ?? ''),
            'location' => self::escapeLatex(self::formatLocation($basics['location'] ?? [])),
            'summary' => self::escapeLatex($basics['summary'] ?? ''),
            'links' => self::formatLinks($basics['links'] ?? []),
            'projects' => self::formatProjects($parsedData['projects'] ?? []),

            // Sections (filtered, with escaped text)
            'skills' => self::formatSkills($parsedData['skills'] ?? []),
            'work' => self::formatWork($parsedData['work'] ?? []),
            'education' => self::formatEducation($parsedData['education'] ?? []),
            'volunteer' => self::formatGenericSection($parsedData['volunteer'] ?? [], ['position', 'role', 'organization', 'summary']),
            'certificates' => self::formatCertificates($parsedData['certificates'] ?? []),
            'publications' => self::formatGenericSection($parsedData['publications'] ?? [], ['name', 'publisher', 'releaseDate', 'url', 'summary']),
            'awards' => self::formatGenericSection($parsedData['awards'] ?? [], ['title', 'awarder', 'date', 'summary']),
            'languages' => self::formatLanguages($parsedData['languages'] ?? []),
            'interests' => self::formatInterests($parsedData['interests'] ?? []),
            'references' => self::formatGenericSection($parsedData['references'] ?? [], ['name', 'reference']),
        ];

        // Add metadata if requested
        if ($includeMetadata) {
            $data['generatedAt'] = now()->format('F j, Y');
            $data['isFiltered'] = !empty($keywords);
            $data['filterKeywords'] = is_array($keywords) ? self::escapeLatex(implode(', ', $keywords)) : self::escapeLatex($keywords ?? '');
        }

        return $data;
    }

    /**
     * Format location object into escaped string.
     */
    protected static function formatLocation(array $location): string
    {
        $parts = array_filter([
            $location['address'] ?? null,
            $location['city'] ?? null,
            $location['region'] ?? null,
            $location['postalCode'] ?? null,
            $location['countryCode'] ?? null,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Format links with escaped URLs and labels.
     * Matches schema: basics.links[] with url, label
     */
    protected static function formatLinks(array $links): array
    {
        return array_map(function ($link) {
            return [
                'url' => self::escapeLatex($link['url'] ?? ''),
                'label' => self::escapeLatex($link['label'] ?? ''),
            ];
        }, $links);
    }

    /**
     * Format skills with escaped text.
     */
    protected static function formatSkills(array $skills): array
    {
        return array_map(function ($skill) {
            return [
                'name' => self::escapeLatex($skill['name'] ?? ''),
                'level' => self::escapeLatex($skill['level'] ?? ''),
                'keywords' => self::escapeLatex(implode(', ', $skill['keywords'] ?? [])),
            ];
        }, $skills);
    }

    /**
     * Format work experience with escaped text.
     */
    protected static function formatWork(array $work): array
    {
        return array_map(function ($job) {
            return [
                'position' => self::escapeLatex($job['position'] ?? ''),
                'employer' => self::escapeLatex($job['name'] ?? $job['employer'] ?? ''),
                'location' => self::escapeLatex($job['location'] ?? ''),
                'startDate' => $job['startDate'] ?? '',
                'endDate' => $job['endDate'] ?? 'Present',
                'summary' => self::escapeLatex($job['summary'] ?? ''),
                'highlights' => array_map(fn($h) => self::escapeLatex($h), $job['highlights'] ?? []),
                'crossReferencedProjects' => $job['crossReferencedProjects'] ?? [],
            ];
        }, $work);
    }

    /**
     * Format projects for template (with escaped text).
     */
    protected static function formatProjects(array $projects): array
    {
        return array_map(function ($project) {
            return [
                'id' => $project['id'] ?? '',
                'name' => self::escapeLatex($project['name'] ?? ''),
                'description' => self::escapeLatex($project['description'] ?? ''),
                'summary' => self::escapeLatex($project['summary'] ?? ''),
                'url' => self::escapeLatex($project['url'] ?? ''),
                'startDate' => $project['startDate'] ?? '',
                'endDate' => $project['endDate'] ?? '',
                'keywords' => array_map(fn($k) => self::escapeLatex($k), $project['keywords'] ?? []),
            ];
        }, $projects);
    }

    /**
     * Format education with ALL schema fields escaped for LaTeX.
     * Matches schema: education[] with programs[], honorSocieties[], etc.
     */
    protected static function formatEducation(array $education): array
    {
        return array_map(function ($edu) {
            return [
                // Core fields
                'institution' => self::escapeLatex($edu['institution'] ?? ''),
                'area' => self::escapeLatex($edu['area'] ?? ''),
                'studyType' => self::escapeLatex($edu['studyType'] ?? ''),
                'location' => self::escapeLatex($edu['location'] ?? ''),
                'url' => self::escapeLatex($edu['url'] ?? ''),
                'subInstitution' => self::escapeLatex($edu['subInstitution'] ?? ''),
                'subInstitutionUrl' => self::escapeLatex($edu['subInstitutionUrl'] ?? ''),
                'startDate' => $edu['startDate'] ?? '',  // ISO8601 dates don't need escaping
                'endDate' => $edu['endDate'] ?? '',
                'gpa' => self::escapeLatex($edu['gpa'] ?? ''),

                // Programs array (nested objects)
                'programs' => array_map(function ($prog) {
                    return [
                        'type' => self::escapeLatex($prog['type'] ?? ''),
                        'designation' => self::escapeLatex($prog['designation'] ?? ''),
                        'name' => self::escapeLatex($prog['name'] ?? ''),
                        'concentration' => self::escapeLatex($prog['concentration'] ?? ''),
                        'minor' => $prog['minor'] ?? false,
                        'gpa' => self::escapeLatex($prog['gpa'] ?? ''),
                        'honors' => self::escapeLatex($prog['honors'] ?? ''),
                    ];
                }, $edu['programs'] ?? []),

                // Arrays of strings
                'courses' => array_map(fn($c) => self::escapeLatex($c), $edu['courses'] ?? []),
                'awards' => array_map(fn($a) => self::escapeLatex($a), $edu['awards'] ?? []),
                'extracurriculars' => array_map(fn($e) => self::escapeLatex($e), $edu['extracurriculars'] ?? []),
                'keywords' => array_map(fn($k) => self::escapeLatex($k), $edu['keywords'] ?? []),

                // Honor societies (nested objects)
                'honorSocieties' => array_map(function ($society) {
                    return [
                        'name' => self::escapeLatex($society['name'] ?? ''),
                        'chapter' => self::escapeLatex($society['chapter'] ?? ''),
                        'memberId' => self::escapeLatex($society['memberId'] ?? ''),
                        'inductionDate' => $society['inductionDate'] ?? '',
                    ];
                }, $edu['honorSocieties'] ?? []),

                // Free text
                'notes' => self::escapeLatex($edu['notes'] ?? ''),
            ];
        }, $education);
    }

    /**
     * Format certificates with escaped text for LaTeX rendering.
     * Matches schema: certificates[] with name, date, issuer, url, id, keywords
     */
    protected static function formatCertificates(array $certificates): array
    {
        return array_map(function ($cert) {
            return [
                'name' => self::escapeLatex($cert['name'] ?? ''),
                'date' => $cert['date'] ?? '',  // Dates don't need escaping (ISO8601)
                'issuer' => self::escapeLatex($cert['issuer'] ?? ''),
                'url' => self::escapeLatex($cert['url'] ?? ''),
                'id' => self::escapeLatex($cert['id'] ?? ''),
                'keywords' => array_map(fn($k) => self::escapeLatex($k), $cert['keywords'] ?? []),
            ];
        }, $certificates);
    }

    /**
     * Format languages with escaped text.
     */
    protected static function formatLanguages(array $languages): array
    {
        return array_map(function ($lang) {
            return [
                'language' => self::escapeLatex($lang['language'] ?? ''),
                'fluency' => self::escapeLatex($lang['fluency'] ?? ''),
            ];
        }, $languages);
    }

    /**
     * Format interests with escaped keywords.
     */
    protected static function formatInterests(array $interests): array
    {
        return array_map(function ($interest) {
            return [
                'name' => self::escapeLatex($interest['name'] ?? ''),
                'keywords' => array_map(fn($k) => self::escapeLatex($k), $interest['keywords'] ?? []),
            ];
        }, $interests);
    }

    /**
     * Generic formatter for sections with specified fields.
     */
    protected static function formatGenericSection(array $items, array $fields): array
    {
        return array_map(function ($item) use ($fields) {
            $formatted = [];
            foreach ($fields as $field) {
                $value = $item[$field] ?? null;
                $formatted[$field] = is_array($value)
                    ? array_map(fn($v) => self::escapeLatex($v), $value)
                    : self::escapeLatex($value ?? '');
            }
            return $formatted;
        }, $items);
    }

    /**
     * Escape special LaTeX characters to prevent compilation errors.
     */
    public static function escapeLatex(?string $text): string
    {
        if (empty($text))
            return '';

        // Order matters: escape backslash first
        $search = ['\\', '{', '}', '&', '%', '$', '#', '_', '^', '~'];
        $replace = [
            '\\textbackslash{}',
            '\\{',
            '\\}',
            '\\&',
            '\\%',
            '\\$',
            '\\#',
            '\\_',
            '\\textasciicircum{}',
            '\\textasciitilde{}'
        ];
        return str_replace($search, $replace, $text);
    }
}