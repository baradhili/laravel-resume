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

        // Filter each section
        $filtered['skills'] = self::filterSkills($parsedData['skills'] ?? [], $keywordList, $matchAll);
        $filtered['work'] = self::filterWork($parsedData['work'] ?? [], $keywordList, $matchAll);
        $filtered['projects'] = self::filterProjects($parsedData['projects'] ?? [], $keywordList, $matchAll);
        $filtered['education'] = self::filterEducation($parsedData['education'] ?? [], $keywordList, $matchAll);
        $filtered['volunteer'] = self::filterVolunteer($parsedData['volunteer'] ?? [], $keywordList, $matchAll);
        $filtered['certifications'] = self::filterCertifications($parsedData['certifications'] ?? [], $keywordList, $matchAll);
        $filtered['publications'] = self::filterPublications($parsedData['publications'] ?? [], $keywordList, $matchAll);
        $filtered['awards'] = self::filterAwards($parsedData['awards'] ?? [], $keywordList, $matchAll);
        $filtered['languages'] = self::filterLanguages($parsedData['languages'] ?? [], $keywordList, $matchAll);
        $filtered['interests'] = self::filterInterests($parsedData['interests'] ?? [], $keywordList, $matchAll);
        $filtered['references'] = self::filterReferences($parsedData['references'] ?? [], $keywordList, $matchAll);

        // 🔹 CRITICAL: Update cross-referenced projects in work to only include filtered projects
        $filtered = self::syncCrossReferences($filtered);

        // Remove empty sections (optional - keeps output clean)
        $filtered = self::removeEmptySections($filtered);

        return $filtered;
    }

    /**
     * Normalize keywords input to array of lowercase trimmed strings.
     */
    protected static function normalizeKeywords(string|array $keywords): array
    {
        if (is_string($keywords)) {
            $keywords = [$keywords];
        }
        
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
     */
    protected static function filterWork(array $work, array $keywords, bool $matchAll): array
    {
        return array_values(array_filter($work, function ($job) use ($keywords, $matchAll) {
            $fieldsToCheck = [
                $job['name'] ?? '',           // Company
                $job['position'] ?? '',       // Position/title
                $job['summary'] ?? '',        // Job summary
                $job['location'] ?? '',       // Location
                implode(' ', $job['highlights'] ?? []), // Highlights
                implode(' ', array_column($job['keywords'] ?? [], 'name') ?? []), // Skills/keywords
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
                implode(' ', $project['keywords'] ?? []),
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
                $edu['studyType'] ?? '',
                $edu['area'] ?? '',
                $edu['score'] ?? '',
                implode(' ', $edu['courses'] ?? []),
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
    protected static function filterVolunteer(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['organization', 'position', 'summary']);
    }
    protected static function filterCertifications(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'issuer', 'summary']);
    }
    protected static function filterPublications(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'publisher', 'summary']);
    }
    protected static function filterAwards(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['title', 'awarder', 'summary']);
    }
    protected static function filterLanguages(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['language', 'fluency']);
    }
    protected static function filterInterests(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['name'], 'keywords');
    }
    protected static function filterReferences(array $items, array $keywords, bool $matchAll): array {
        return self::filterGenericItems($items, $keywords, $matchAll, ['name', 'reference']);
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
        $sections = ['skills', 'work', 'projects', 'education', 'volunteer', 
                    'certifications', 'publications', 'awards', 'languages', 
                    'interests', 'references'];
        
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