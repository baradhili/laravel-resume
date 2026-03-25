<?php

namespace App\Services;

use JsonSchema\Validator as JsonSchemaLibraryValidator;
use JsonSchema\Constraints\Constraint;
use Illuminate\Support\Facades\Log;

class JsonSchemaValidator
{
    /**
     * Validate JSON data against a schema file.
     *
     * @param array|string $jsonData The JSON data to validate (array or JSON string)
     * @param string $schemaFile Path to schema file relative to resources/schemas/
     * @param bool $autoFetchSchema Whether to auto-fetch schema if missing
     * @return array { valid: bool, errors: array }
     */
    public static function validate(
        array|string $jsonData,
        string $schemaFile = 'json-resume-merged.json',
        bool $autoFetchSchema = true
    ): array {
        // Decode if JSON string provided
        if (is_string($jsonData)) {
            $jsonData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        }

        // Build schema path
        $schemaPath = resource_path("schemas/{$schemaFile}");

        // Auto-fetch schema if missing and enabled
        if (!file_exists($schemaPath)) {
            if ($autoFetchSchema) {
                Log::info("Schema not found, attempting auto-fetch: {$schemaFile}");
                try {
                    \Artisan::call('schema:fetch', ['--force' => true]);
                } catch (\Throwable $e) {
                    Log::error("Failed to auto-fetch schema: {$e->getMessage()}");
                }
            }
            
            if (!file_exists($schemaPath)) {
                return [
                    'valid' => false,
                    'errors' => ["Schema file not found: {$schemaFile}"],
                ];
            }
        }

        // Load schema as OBJECT (required by library)
        $schema = json_decode(file_get_contents($schemaPath), false, 512, JSON_THROW_ON_ERROR);

        // Convert data to OBJECT (required for by-reference passing in PHP 8+)
        $dataToValidate = json_decode(json_encode($jsonData), false, 512, JSON_THROW_ON_ERROR);

        // Initialize validator
        $validator = new JsonSchemaLibraryValidator();
        
        // Validate - pass variables (not expressions) to satisfy PHP 8+ reference rules
        $validator->validate(
            $dataToValidate,
            $schema,
            Constraint::CHECK_MODE_APPLY_DEFAULTS
        );

        // Return results if valid
        if ($validator->isValid()) {
            return ['valid' => true, 'errors' => []];
        }

        // 🔹 ROBUST ERROR FORMATTING WITH CONSTRAINT PARAMS 🔹
        $rawErrors = $validator->getErrors();
        
        // Debug log raw structure (only in debug mode)
        if (config('app.debug')) {
            Log::debug('Schema validation raw errors:', [
                'count' => count($rawErrors),
                'first_error' => $rawErrors[0] ?? null,
                'types' => array_map(fn($e) => gettype($e), array_slice($rawErrors, 0, 3)),
            ]);
        }

        // Format each error to a clean string with constraint details
        $errors = [];
        foreach ($rawErrors as $error) {
            // Skip if not an array (defensive)
            if (!is_array($error)) {
                continue;
            }
            
            // Extract basic fields
            $property = $error['property'] ?? $error['pointer'] ?? 'root';
            $message = $error['message'] ?? 'Validation failed';
            
            // 🔹 FIX: Handle constraint as object/array - extract name and params
            $constraintRaw = $error['constraint'] ?? null;
            $constraintName = null;
            $constraintParams = null;
            
            if (is_array($constraintRaw)) {
                $constraintName = $constraintRaw['name'] ?? null;
                $constraintParams = $constraintRaw['params'] ?? null;
            } elseif (is_object($constraintRaw)) {
                $constraintName = $constraintRaw->name ?? null;
                $constraintParams = isset($constraintRaw->params) 
                    ? (array) $constraintRaw->params 
                    : null;
            } elseif (is_string($constraintRaw)) {
                $constraintName = $constraintRaw;
            }
            
            // Clean property path (remove "root." prefix, keep array notation like work[3])
            $property = preg_replace('/^root\.?/', '', $property);
            $field = $property ?: 'document';
            
            // Build message parts (all explicitly cast to strings)
            $parts = [];
            
            // Add field path if not root
            if ($field && $field !== 'document') {
                $parts[] = "[{$field}]";
            }
            
            // Add main message
            $parts[] = (string) $message;
            
            // 🔹 FIX: Add constraint details with params for richer errors
            if ($constraintName && is_string($constraintName)) {
                if ($constraintParams && is_array($constraintParams) && !empty($constraintParams)) {
                    // Format params as key=value pairs
                    $paramParts = [];
                    foreach ($constraintParams as $key => $value) {
                        $valStr = is_array($value) 
                            ? json_encode($value) 
                            : (string) $value;
                        $paramParts[] = "{$key}={$valStr}";
                    }
                    $paramsStr = implode(', ', $paramParts);
                    $parts[] = "(constraint: {$constraintName}: {$paramsStr})";
                } else {
                    $parts[] = "(constraint: {$constraintName})";
                }
            }
            
            // Optional: Add JSON pointer for debugging (uncomment if helpful)
            // if (config('app.debug') && !empty($error['pointer'])) {
            //     $parts[] = "(pointer: {$error['pointer']})";
            // }
            
            // Join parts and add to errors array
            $errors[] = trim(implode(' ', $parts));
        }

        // Final safeguard: ensure we have string errors only
        $errors = array_values(array_filter($errors, fn($e) => is_string($e) && $e !== ''));
        
        // Fallback if somehow still empty but validation failed
        if (empty($errors)) {
            $errors = ['Schema validation failed (no detailed errors available)'];
        }

        return [
            'valid' => false,
            'errors' => $errors, // Guaranteed: array of clean strings
        ];
    }

    /**
     * Quick validation - returns boolean only.
     */
    public static function isValid(
        array|string $jsonData,
        string $schemaFile = 'json-resume-merged.json'
    ): bool {
        return self::validate($jsonData, $schemaFile)['valid'];
    }

    /**
     * Get validation errors only (convenience method).
     */
    public static function errors(
        array|string $jsonData,
        string $schemaFile = 'json-resume-merged.json'
    ): array {
        return self::validate($jsonData, $schemaFile)['errors'];
    }
}