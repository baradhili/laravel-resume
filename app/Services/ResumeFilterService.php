<?php

namespace App\Services;

use Illuminate\Support\Str;

class ResumeFilterService
{
    /**
     * Filter resume parsed_data by keywords.
     *
     * @param array $parsedData The resume's parsed_data array
     * @param string|array $keywords Single keyword or array of keywords to filter by
     * @param bool $matchAll If true, item must match ALL keywords; if false, match ANY
     * @return array Filtered parsed_data
     */
    public static function filter(
        array $parsedData,
        string|array $keywords,
        bool $matchAll = false
    ): array {
        // Normalize keywords to array of lowercase trimmed strings
        $keywordList = self::normalizeKeywords($keywords);

        // If no keywords, return original data unchanged
        if (empty($keywordList)) {
            return $parsedData;
        }

        $filtered = $parsedData;
        // check projects first - if project matches filter then work item that crossrefs it is included
        $filteredProjects = self::filterProjects($parsedData['projects'] ?? [], $keywordList, $matchAll);
        $matchingProjectIds = self::extractProjectIdentifiers($filteredProjects);

        // Filter each section
        $filtered['skills'] = self::filterSkills($parsedData['skills'] ?? [], $keywordList, $matchAll);
        $filtered['work'] = self::filterWork($parsedData['work'] ?? [], $keywordList, $matchAll, $matchingProjectIds);
        $filtered['projects'] = $filteredProjects; // Use pre-filtered projects
        $filtered['education'] = self::filterEducation($parsedData['education'] ?? [], $keywordList, $matchAll);
        $filtered['volunteer'] = self::filterVolunteer($parsedData['volunteer'] ?? [], $keywordList, $matchAll);
        $filtered['certificates'] = self::filterCertificates($parsedData['certificates'] ?? [], $keywordList, $matchAll);
        $filtered['publications'] = self::filterPublications($parsedData['publications'] ?? [], $keywordList, $matchAll);
        $filtered['awards'] = self::filterAwards($parsedData['awards'] ?? [], $keywordList, $matchAll);
        $filtered['languages'] = self::filterLanguages($parsedData['languages'] ?? [], $keywordList, $matchAll);
        $filtered['interests'] = self::filterInterests($parsedData['interests'] ?? [], $keywordList, $matchAll);
        $filtered['references'] = self::filterReferences($parsedData['references'] ?? [], $keywordList, $matchAll);

        // Update cross-referenced projects in work to only include filtered projects
        $filtered = self::syncCrossReferences($filtered);

        // Remove empty sections (optional - keeps output clean)
        $filtered = self::removeEmptySections($filtered);

        return $filtered;
    }

    /**
     * Normalize keywords input to array of lowercase trimmed strings.
     * 
     * Handles:
     * - Single string: "resource management" → ["resource management"]
     * - Comma-separated string: "resource,management" → ["resource", "management"] 
     * - Array of strings: ["resource management", "workforce planning"] → normalized array
     * - JSON-encoded string: '["resource management"]' → decoded + normalized array
     */
    protected static function normalizeKeywords(string|array $keywords): array
    {
        // Handle JSON-encoded string input (e.g., from API request body)
        if (is_string($keywords) && str_starts_with(trim($keywords), '[')) {
            $decoded = json_decode($keywords, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $keywords = $decoded;
            }
        }

        // Handle comma-separated string input (fallback if controller doesn't split)
        if (is_string($keywords) && str_contains($keywords, ',')) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        // Ensure we have an array (wrap single string)
        if (is_string($keywords)) {
            $keywords = [$keywords];
        }

        // Normalize: trim whitespace, lowercase, filter empty values
        return array_values(array_filter(array_map(
            fn($k) => Str::of($k)->trim()->lower()->toString(),
            $keywords
        ), fn($k) => !empty($k)));
    }

    /**
     * Check if text matches any/all keywords.
     */
    protected static function matchesKeywords(string $text, array $keywords, bool $matchAll): bool
    {
        $textLower = Str::lower($text);

        if ($matchAll) {
            // Must contain ALL keywords
            foreach ($keywords as $keyword) {
                if (!Str::contains($textLower, $keyword)) {
                    return false;
                }
            }
            return true;
        } else {
            // Must contain ANY keyword
            foreach ($keywords as $keyword) {
                if (Str::contains($textLower, $keyword)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Extract identifiers (id and/or name) from filtered projects for cross-reference lookups.
     */
    protected static function extractProjectIdentifiers(array $projects): array
    {
        $identifiers = [];
        foreach ($projects as $project) {
            if (!empty($project['id'])) {
                $identifiers[$project['id']] = true;
            }
            if (!empty($project['name'])) {
                $identifiers[$project['name']] = true;
            }
        }
        return $identifiers;
    }

    /**
     * Filter skills section.
     */
    protected static function filterSkills(array $skills, array $keywords, bool $matchAll): array
    {
        return array_values(array_filter($skills, function ($skill) use ($keywords, $matchAll) {
            // Check skill name
            if (!empty($skill['name']) && self::matchesKeywords($skill['name'], $keywords, $matchAll)) {
                return true;
            }
            // Check keywords array within skill
            if (!empty($skill['keywords']) && is_array($skill['keywords'])) {
                foreach ($skill['keywords'] as $kw) {
                    if (self::matchesKeywords($kw, $keywords, $matchAll)) {
                        return true;
                    }
                }
            }
            // Check level
            if (!empty($skill['level']) && self::matchesKeywords($skill['level'], $keywords, $matchAll)) {
                return true;
            }
            return false;
        }));
    }

    /**
     * Filter work experience section.
     * 
     * Includes work items that reference any project matching the keywords,
     * even if the work item itself doesn't match keywords directly.
     */
    protected static function filterWork(array $work, array $keywords, bool $matchAll, array $matchingProjectIds = []): array
    {
        return array_values(array_filter($work, function ($job) use ($keywords, $matchAll, $matchingProjectIds) {
            // Check if work item matches keywords directly in schema-defined fields
            $fieldsToCheck = [
                $job['employer'] ?? '',                  // Company name
                $job['position'] ?? '',                  // Role title
                $job['summary'] ?? '',                   // Responsibilities overview
                $job['description'] ?? '',               // Company description
                $job['location'] ?? '',                  // Job location
                implode(' ', $job['highlights'] ?? []),  // Key achievements (array of strings)
            ];

            foreach ($fieldsToCheck as $field) {
                if (!empty($field) && self::matchesKeywords($field, $keywords, $matchAll)) {
                    return true;
                }
            }

            // Check keywords array (schema: array of strings only)
            if (!empty($job['keywords']) && is_array($job['keywords'])) {
                // keywords is strictly string[] per types.json
                $keywordsText = implode(' ', array_filter($job['keywords'], 'is_string'));
                if (!empty($keywordsText) && self::matchesKeywords($keywordsText, $keywords, $matchAll)) {
                    return true;
                }
            }

            // 🔹 Include work item if it references ANY project that matched keywords
            if (!empty($job['crossReferencedProjects']) && is_array($job['crossReferencedProjects'])) {
                foreach ($job['crossReferencedProjects'] as $ref) {
                    if (isset($matchingProjectIds[$ref])) {
                        return true;
                    }
                }
            }

            return false;
        }));
    }

    /**
     * Filter projects section.
     */
    protected static function filterProjects(array $projects, array $keywords, bool $matchAll): array
    {
        return array_values(array_filter($projects, function ($project) use ($keywords, $matchAll) {
            $fieldsToCheck = [
                $project['name'] ?? '',
                $project['description'] ?? '',
                $project['summary'] ?? '',
                $project['entity'] ?? '',
                $project['type'] ?? '',

                implode(' ', array_filter($project['keywords'] ?? [], 'is_string')),
                implode(' ', $project['highlights'] ?? []),
            ];

            foreach ($fieldsToCheck as $field) {
                if (!empty($field) && self::matchesKeywords($field, $keywords, $matchAll)) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Filter education section.
     */
    protected static function filterEducation(array $education, array $keywords, bool $matchAll): array
    {
        return array_values(array_filter($education, function ($edu) use ($keywords, $matchAll) {
            $fieldsToCheck = [
                $edu['institution'] ?? '',
                $edu['subInstitution'] ?? '',           // ✅ Schema-compliant
                $edu['area'] ?? '',                     // ✅ Was 'studyType'
                $edu['location'] ?? '',
                $edu['gpa'] ?? '',                      // ✅ Was 'score'
                $edu['notes'] ?? '',
                // Programs array (schema: objects with name, concentration, etc.)
                implode(' ', array_map(
                    fn($p) => implode(' ', [
                        $p['name'] ?? '',
                        $p['designation'] ?? '',
                        $p['concentration'] ?? '',
                        $p['type'] ?? '',
                    ]),
                    $edu['programs'] ?? []
                )),
                // Courses array (schema: string[])
                implode(' ', $edu['courses'] ?? []),
                // Awards array (schema: string[])
                implode(' ', $edu['awards'] ?? []),
                // Extracurriculars array (schema: string[])
                implode(' ', $edu['extracurriculars'] ?? []),
                // Keywords (schema: string[])
                implode(' ', $edu['keywords'] ?? []),
            ];

            foreach ($fieldsToCheck as $field) {
                if (!empty($field) && self::matchesKeywords($field, $keywords, $matchAll)) {
                    return true;
                }
            }
            return false;
        }));
    }

    // Generic filter methods for other sections (can be expanded as needed)
    protected static function filterVolunteer(array $items, array $keywords, bool $matchAll): array
    {
        return self::filterGenericItems($items, $keywords, $matchAll, ['organization', 'position', 'summary']);
    }
    protected static function filterCertificates(array $items, array $keywords, bool $matchAll): array
{
    // ✅ certificates schema: 'name', 'issuer', 'summary', 'keywords'
    return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'issuer', 'summary'], 'keywords');
}
    protected static function filterPublications(array $items, array $keywords, bool $matchAll): array
    {
        return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'publisher', 'summary']);
    }
    protected static function filterAwards(array $items, array $keywords, bool $matchAll): array
    {
        return self::filterGenericItems($items, $keywords, $matchAll, ['title', 'awarder', 'summary']);
    }
    protected static function filterLanguages(array $items, array $keywords, bool $matchAll): array
{
    // ✅ languages schema: only 'language' and 'fluency' fields
    return self::filterGenericItems($items, $keywords, $matchAll, ['language', 'fluency']);
    // Removed 'keywords' parameter - doesn't exist in schema
}
    protected static function filterInterests(array $items, array $keywords, bool $matchAll): array
{
    // ✅ interests schema: 'name' + 'keywords' (string[])
    return self::filterGenericItems($items, $keywords, $matchAll, ['name'], 'keywords');
}
    protected static function filterReferences(array $items, array $keywords, bool $matchAll): array
{
    // ✅ references schema: only 'name' and 'reference' fields
    return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'reference']);
    // Removed 'keywords' parameter - doesn't exist in schema
}

    /**
     * Generic filter for sections with similar structure.
     */
    protected static function filterGenericItems(
        array $items,
        array $keywords,
        bool $matchAll,
        array $fields,
        string $keywordsField = null
    ): array {
        return array_values(array_filter($items, function ($item) use ($keywords, $matchAll, $fields, $keywordsField) {
            // Check specified fields
            foreach ($fields as $field) {
                if (!empty($item[$field]) && self::matchesKeywords($item[$field], $keywords, $matchAll)) {
                    return true;
                }
            }
            // Check keywords sub-array if specified
            if ($keywordsField && !empty($item[$keywordsField]) && is_array($item[$keywordsField])) {
                foreach ($item[$keywordsField] as $kw) {
                    if (self::matchesKeywords($kw, $keywords, $matchAll)) {
                        return true;
                    }
                }
            }
            return false;
        }));
    }

    /**
     * 🔹 Sync cross-referenced projects: remove references to filtered-out projects.
     */
    protected static function syncCrossReferences(array $filtered): array
    {
        // Build set of remaining project identifiers (id and/or name)
        $remainingProjectIds = [];
        foreach ($filtered['projects'] ?? [] as $project) {
            if (!empty($project['id'])) {
                $remainingProjectIds[$project['id']] = true;
            }
            if (!empty($project['name'])) {
                $remainingProjectIds[$project['name']] = true;
            }
        }

        // Update work items' crossReferencedProjects
        if (!empty($filtered['work']) && is_array($filtered['work'])) {
            $filtered['work'] = array_map(function ($job) use ($remainingProjectIds) {
                if (!empty($job['crossReferencedProjects']) && is_array($job['crossReferencedProjects'])) {
                    $job['crossReferencedProjects'] = array_values(array_filter(
                        $job['crossReferencedProjects'],
                        fn($ref) => isset($remainingProjectIds[$ref])
                    ));
                }
                return $job;
            }, $filtered['work']);
        }

        return $filtered;
    }

    /**
     * Remove sections that are empty after filtering (optional cleanup).
     */
    protected static function removeEmptySections(array $data): array
    {
        $sections = [
            'skills',
            'work',
            'projects',
            'education',
            'volunteer',
            'certifications',
            'publications',
            'awards',
            'languages',
            'interests',
            'references'
        ];

        foreach ($sections as $section) {
            if (isset($data[$section]) && is_array($data[$section]) && empty($data[$section])) {
                unset($data[$section]);
            }
        }

        return $data;
    }

    /**
     * Get filter metadata for UI display.
     */
    public static function getFilterMetadata(array $original, array $filtered): array
    {
        return [
            'original_counts' => [
                'skills' => count($original['skills'] ?? []),
                'work' => count($original['work'] ?? []),
                'projects' => count($original['projects'] ?? []),
                'education' => count($original['education'] ?? []),
            ],
            'filtered_counts' => [
                'skills' => count($filtered['skills'] ?? []),
                'work' => count($filtered['work'] ?? []),
                'projects' => count($filtered['projects'] ?? []),
                'education' => count($filtered['education'] ?? []),
            ],
            'removed_counts' => [
                'skills' => (count($original['skills'] ?? [])) - (count($filtered['skills'] ?? [])),
                'work' => (count($original['work'] ?? [])) - (count($filtered['work'] ?? [])),
                'projects' => (count($original['projects'] ?? [])) - (count($filtered['projects'] ?? [])),
                'education' => (count($original['education'] ?? [])) - (count($filtered['education'] ?? [])),
            ],
        ];
    }
}