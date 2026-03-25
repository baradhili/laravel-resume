<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchResumeSchema extends Command
{
    protected $signature = 'schema:fetch 
                            {--force : Overwrite existing schema files}
                            {--output= : Output filename (default: json-resume-merged.json)}
                            {--branch=master : Git branch to fetch from}';
    
    protected $description = 'Fetch and merge JSON Resume schema files from GitHub';

    // GitHub repo configuration
    protected string $repoOwner = 'baradhili';
    protected string $repoName = 'resume-schema';
    protected string $baseUrl = 'https://raw.githubusercontent.com';

    // Schema files to fetch (in dependency order)
    protected array $schemaFiles = [
        'types' => 'types.json',
        'keywords-dictionary' => 'keywords-dictionary-schema.json',
        'job' => 'job-schema.json',
        'main' => 'schema.json',
    ];

    public function handle(): int
    {
        $this->info('🔄 Fetching JSON Resume schema files from GitHub...');

        $branch = $this->option('branch') ?? 'master';
        $outputFile = $this->option('output') ?? 'json-resume-merged.json';
        $force = $this->option('force');

        // Ensure output directory exists
        $outputPath = resource_path("schemas/{$outputFile}");
        File::ensureDirectoryExists(dirname($outputPath));

        // Fetch all schema files
        $schemas = [];
        foreach ($this->schemaFiles as $key => $filename) {
            $url = "{$this->baseUrl}/{$this->repoOwner}/{$this->repoName}/refs/heads/{$branch}/{$filename}";  
            
            $this->line("  ⬇️  Fetching {$filename}... from {$url}");
            
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                $this->error("  ✗ Failed to fetch {$filename}: HTTP {$response->status()}");
                return Command::FAILURE;
            }

            $content = $response->body();
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("  ✗ Invalid JSON in {$filename}: " . json_last_error_msg());
                return Command::FAILURE;
            }

            $schemas[$key] = $decoded;
            $this->line("  ✓ Loaded {$filename}");
        }

        // Merge schemas
        $this->line("\n🔗 Merging schemas...");
        $merged = $this->mergeSchemas($schemas);

        // Add metadata
        $merged['$comment'] = "Merged from {$this->repoOwner}/{$this->repoName} on " . now()->toIso8601String();
        $merged['$mergedAt'] = now()->toIso8601String();
        $merged['$sourceFiles'] = array_values($this->schemaFiles);

        // Write to file
        if (File::exists($outputPath) && !$force) {
            if (!$this->confirm("File {$outputFile} already exists. Overwrite?")) {
                $this->warn('⊗ Skipped. Use --force to overwrite.');
                return Command::SUCCESS;
            }
        }

        File::put($outputPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->info("\n✅ Schema merged and saved to: {$outputPath}");
        $this->info("📊 File size: " . $this->formatBytes(filesize($outputPath)));

        return Command::SUCCESS;
    }

    /**
     * Merge multiple schema files into one consolidated schema
     */
    protected function mergeSchemas(array $schemas): array
    {
        // Start with the main schema
        $merged = $schemas['main'] ?? [];

        // Merge types.json - add to $defs if present
        if (!empty($schemas['types']['$defs'])) {
            $merged['$defs'] = array_merge(
                $merged['$defs'] ?? [],
                $schemas['types']['$defs']
            );
            $this->line("  ✓ Merged " . count($schemas['types']['$defs']) . " type definitions");
        }

        // Merge keywords-dictionary-schema.json
        if (!empty($schemas['keywords-dictionary'])) {
            // Add keywords dictionary to $defs
            if (isset($schemas['keywords-dictionary']['$defs'])) {
                $merged['$defs'] = array_merge(
                    $merged['$defs'] ?? [],
                    $schemas['keywords-dictionary']['$defs']
                );
                $this->line("  ✓ Merged keywords dictionary definitions");
            }
            // Merge any root-level properties if needed
            $merged = $this->deepMerge($merged, $schemas['keywords-dictionary'], ['properties', '$defs']);
        }

        // Replace/merge work schema from job-schema.json
        if (!empty($schemas['job'])) {
            // The job-schema.json likely defines a "work" item schema
            // We need to update the main schema's work.items.$ref or work.items definition
            
            if (isset($merged['properties']['work']['items'])) {
                // Replace the work items definition with the job schema
                $merged['properties']['work']['items'] = $this->mergeSchemaDefinitions(
                    $merged['properties']['work']['items'],
                    $schemas['job']
                );
                $this->line("  ✓ Updated work schema with job-schema.json");
            }
            
            // Also add any job-related $defs
            if (!empty($schemas['job']['$defs'])) {
                $merged['$defs'] = array_merge(
                    $merged['$defs'] ?? [],
                    $schemas['job']['$defs']
                );
                $this->line("  ✓ Merged " . count($schemas['job']['$defs']) . " job definitions");
            }
        }

        // Clean up any empty $defs
        if (isset($merged['$defs']) && empty($merged['$defs'])) {
            unset($merged['$defs']);
        }

        return $merged;
    }

    /**
     * Deep merge two schema arrays, focusing on specific keys
     */
    protected function deepMerge(array $target, array $source, array $mergeKeys = []): array
    {
        foreach ($source as $key => $value) {
            if (in_array($key, $mergeKeys) && isset($target[$key]) && is_array($target[$key]) && is_array($value)) {
                $target[$key] = array_merge($target[$key], $value);
            } elseif (!isset($target[$key])) {
                $target[$key] = $value;
            }
        }
        return $target;
    }

    /**
     * Merge schema definitions, handling $ref and nested properties
     */
    protected function mergeSchemaDefinitions(array $base, array $override): array
    {
        // If override has properties, merge them
        if (!empty($override['properties'])) {
            $base['properties'] = array_merge($base['properties'] ?? [], $override['properties']);
        }
        
        // If override has required fields, merge them
        if (!empty($override['required'])) {
            $base['required'] = array_unique(array_merge($base['required'] ?? [], $override['required']));
        }
        
        // Merge any additional schema keywords
        $schemaKeys = ['type', 'description', 'examples', 'default', 'minItems', 'maxItems'];
        foreach ($schemaKeys as $key) {
            if (isset($override[$key]) && !isset($base[$key])) {
                $base[$key] = $override[$key];
            }
        }
        
        return $base;
    }

    /**
     * Format bytes to human-readable string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max(0, $bytes);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}