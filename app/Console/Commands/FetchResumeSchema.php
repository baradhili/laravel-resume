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
                            {--branch=master : Git branch to fetch from}
                            {--force-defs-syntax : Force use of $defs instead of definitions}
                            {--use-tabs : Use tabs instead of spaces for indentation}';
    
    protected $description = 'Fetch and merge JSON Resume schema files (types.json → schema.json)';

    // GitHub repo configuration
    protected string $repoOwner = 'baradhili';
    protected string $repoName = 'resume-schema';
    protected string $baseUrl = 'https://raw.githubusercontent.com';

    // Only these two files are merged (per Python script logic)
    protected array $schemaFiles = [
        'types' => 'types.json',
        'main' => 'schema.json',
    ];

    // Definition key names per JSON Schema draft versions
    protected const DEFS_KEY_OLD = 'definitions';
    protected const DEFS_KEY_NEW = '$defs';

    public function handle(): int
    {
        $this->info('🔄 Fetching JSON Resume schema files from GitHub...');

        $branch = $this->option('branch');
        $outputFile = $this->option('output') ?? 'json-resume-merged.json';
        $force = $this->option('force');
        $forceDefsSyntax = $this->option('force-defs-syntax');
        $useTabs = $this->option('use-tabs');

        // Ensure output directory exists
        $outputPath = resource_path("schemas/{$outputFile}");
        File::ensureDirectoryExists(dirname($outputPath));

        // Fetch schema files
        $schemas = [];
        foreach ($this->schemaFiles as $key => $filename) {
            $url = "{$this->baseUrl}/{$this->repoOwner}/{$this->repoName}/refs/heads/{$branch}/{$filename}";
            
            $this->line("  ⬇️  Fetching {$filename}...");
            
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                $this->error("  ✗ Failed to fetch {$filename}: HTTP {$response->status()}");
                return Command::FAILURE;
            }

            $content = $response->body();
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $schemas[$key] = $decoded;
            $this->line("  ✓ Loaded {$filename}");
        }

        // Merge schemas using Python script logic
        $this->line("\n🔗 Merging schemas (types.json → schema.json)...");
        $merged = $this->mergeSchemas(
            $schemas['types'],
            $schemas['main'],
            $forceDefsSyntax
        );

        // Add metadata
        $merged['$comment'] = "Merged from {$this->repoOwner}/{$this->repoName} on " . now()->toIso8601String();
        $merged['$mergedAt'] = now()->toIso8601String();
        $merged['$sourceFiles'] = ['types.json', 'schema.json'];

        // Encode with proper indentation
        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        if (!$useTabs) {
            $json = json_encode($merged, $options);
        } else {
            // Encode with spaces, then convert to tabs
            $json = json_encode($merged, $options);
            $json = preg_replace('/^ {4}/m', "\t", $json); // 4 spaces → 1 tab
        }

        // Write to file
        if (File::exists($outputPath) && !$force) {
            if (!$this->confirm("File {$outputFile} already exists. Overwrite?")) {
                $this->warn('⊗ Skipped. Use --force to overwrite.');
                return Command::SUCCESS;
            }
        }

        File::put($outputPath, $json);
        
        $this->info("\n✅ Schema merged and saved to: {$outputPath}");
        $this->info("📊 File size: " . $this->formatBytes(filesize($outputPath)));

        return Command::SUCCESS;
    }

    /**
     * Merge types.json definitions into schema.json, including only used refs.
     * Replicates Python merge_schemas() logic exactly.
     */
    protected function mergeSchemas(array $typesSchema, array $schema, bool $forceDefsSyntax): array
    {
        $typesFilename = 'types.json';

        // Verify schema versions match
        $typesSchemaVer = $typesSchema['$schema'] ?? '';
        $schemaVer = $schema['$schema'] ?? '';
        
        if ($typesSchemaVer !== $schemaVer) {
            $this->error("Error: Schema version mismatch between schema.json and types.json");
            $this->error("  schema.json: {$schemaVer}");
            $this->error("  types.json:  {$typesSchemaVer}");
            exit(1);
        }

        // Determine definition key name based on draft version
        $defKeyname = self::DEFS_KEY_OLD;
        $defOutKeyname = self::DEFS_KEY_OLD;
        
        if (str_contains($typesSchemaVer, 'draft/2020-12/schema')) {
            $defKeyname = self::DEFS_KEY_NEW;
            $defOutKeyname = self::DEFS_KEY_NEW;
        }

        // Force $defs syntax if requested
        if ($forceDefsSyntax) {
            $defOutKeyname = self::DEFS_KEY_NEW;
        }

        // Extract ALL definitions from types.json
        $allDefs = $typesSchema[$defKeyname] ?? [];

        // Find ALL $ref values used in main schema that point to types.json
        $usedRefs = [];
        $this->collectRefs($schema, $typesFilename, $defKeyname, $usedRefs);

        // Filter definitions - ONLY include used ones
        $usedDefs = [];
        foreach ($usedRefs as $defName) {
            if (isset($allDefs[$defName])) {
                $usedDefs[$defName] = $allDefs[$defName];
            }
        }

        $this->line("  ✓ Found " . count($usedRefs) . " referenced definitions");
        $this->line("  ✓ Including " . count($usedDefs) . " definitions in merged schema");

        // Remove old definitions key if present, add merged used definitions
        if (isset($schema[$defKeyname])) {
            unset($schema[$defKeyname]);
        }
        $schema[$defOutKeyname] = $usedDefs;

        // Fix all external refs to internal refs
        $this->fixRefs($schema, $typesFilename, $defKeyname, $defOutKeyname);

        // If forcing $defs syntax, update the $schema identifier
        if ($forceDefsSyntax && $defOutKeyname === self::DEFS_KEY_NEW) {
            $schema['$schema'] = 'https://json-schema.org/draft/2020-12/schema';
        }

        return $schema;
    }

    /**
     * Recursively collect all $ref values that point to types.json definitions.
     */
    protected function collectRefs(mixed $obj, string $typesFilename, string $defKeyname, array &$usedRefs): void
    {
        if (is_array($obj)) {
            if (isset($obj['$ref']) && is_string($obj['$ref'])) {
                $refVal = $obj['$ref'];
                $prefix = "{$typesFilename}#/{$defKeyname}/";
                
                if (str_starts_with($refVal, $prefix)) {
                    $defName = substr($refVal, strlen($prefix));
                    if (!in_array($defName, $usedRefs)) {
                        $usedRefs[] = $defName;
                    }
                }
            }
            
            foreach ($obj as $value) {
                $this->collectRefs($value, $typesFilename, $defKeyname, $usedRefs);
            }
        }
    }

    /**
     * Recursively convert external refs to internal refs.
     */
    protected function fixRefs(mixed &$obj, string $typesFilename, string $defKeyname, string $defOutKeyname): void
    {
        if (is_array($obj)) {
            if (isset($obj['$ref']) && is_string($obj['$ref'])) {
                $refVal = $obj['$ref'];
                $prefix = "{$typesFilename}#/{$defKeyname}/";
                
                if (str_starts_with($refVal, $prefix)) {
                    $defName = substr($refVal, strlen($prefix));
                    $obj['$ref'] = "#/{$defOutKeyname}/{$defName}";
                }
            }
            
            foreach ($obj as &$value) {
                $this->fixRefs($value, $typesFilename, $defKeyname, $defOutKeyname);
            }
            unset($value); // Break reference
        }
    }

    /**
     * Format bytes to human-readable string.
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