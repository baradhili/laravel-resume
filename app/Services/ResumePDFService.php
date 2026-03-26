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

        // Define absolute path to fonts (if using custom fonts)
        $fontPath = storage_path('fonts/');

        //define path to latex additional classes and styles
        $latexDir = storage_path('latex/');

        // Ensure font directory exists
        if (!is_dir($fontPath)) {
            mkdir($fontPath, 0755, true);
        }

        // Generate PDF using Blade view with hardcoded environment variables
        return (new Latex('latex.resume', $options['metadata'] ?? null))
            ->with($templateData)
            ->binPath($binPath)
            ->env([
                'HOME' => '/tmp',
                'PATH' => '/usr/local/bin:/usr/bin:/bin',
                'TEXMFVAR' => '/tmp/texmfv',
                'OSFONTDIR' => $fontPath,
                'TEXINPUTS' => $latexDir . '://:',
                'FONTCONFIG_PATH' => '/tmp',
                'XDG_CACHE_HOME' => '/tmp',
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

        // Define absolute path to fonts
        $fontPath = storage_path('fonts/');
        //define path to latex additional classes and styles
        $latexDir = storage_path('latex/');

        if (!is_dir($fontPath)) {
            mkdir($fontPath, 0755, true);
        }

        // Generate and save PDF with hardcoded environment variables
        (new Latex('latex.resume'))
            ->with($templateData)
            ->binPath($binPath)
            ->env([
                'HOME' => '/tmp',
                'PATH' => '/usr/local/bin:/usr/bin:/bin',
                'TEXMFVAR' => '/tmp/texmfv',
                'OSFONTDIR' => $fontPath . '://:/usr/local/share/fonts//:/usr/share/fonts//',
                'TEXINPUTS' => $latexDir . '://:',
                'FONTCONFIG_PATH' => '/tmp',
                'XDG_CACHE_HOME' => '/tmp',
            ])
            ->savePdf($fullPath);

        return $fullPath;
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
            'profiles' => self::formatProfiles($basics['profiles'] ?? []),
            'projects' => self::formatProjects($parsedData['projects'] ?? []),

            // Sections (filtered, with escaped text)
            'skills' => self::formatSkills($parsedData['skills'] ?? []),
            'work' => self::formatWork($parsedData['work'] ?? []),
            'education' => self::formatEducation($parsedData['education'] ?? []),
            'volunteer' => self::formatGenericSection($parsedData['volunteer'] ?? [], ['position', 'role', 'organization', 'summary']),
            'certifications' => self::formatGenericSection($parsedData['certifications'] ?? [], ['name', 'issuer', 'date', 'url']),
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
     * Format profiles with escaped URLs and names.
     */
    protected static function formatProfiles(array $profiles): array
    {
        return array_map(function ($profile) {
            return [
                'network' => self::escapeLatex($profile['network'] ?? ''),
                'username' => self::escapeLatex($profile['username'] ?? ''),
                'url' => self::escapeLatex($profile['url'] ?? ''),
            ];
        }, $profiles);
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
     * Format education with escaped text.
     */
    protected static function formatEducation(array $education): array
    {
        return array_map(function ($edu) {
            return [
                'studyType' => self::escapeLatex($edu['studyType'] ?? ''),
                'area' => self::escapeLatex($edu['area'] ?? ''),
                'institution' => self::escapeLatex($edu['institution'] ?? ''),
                'startDate' => $edu['startDate'] ?? '',
                'endDate' => $edu['endDate'] ?? '',
                'score' => self::escapeLatex($edu['score'] ?? ''),
                'courses' => array_map(fn($c) => self::escapeLatex($c), $edu['courses'] ?? []),
            ];
        }, $education);
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