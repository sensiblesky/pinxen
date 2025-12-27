<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SchemaDriftService
{
    /**
     * Parse OpenAPI/Swagger specification.
     *
     * @param string $content JSON or YAML content
     * @return array|null Parsed schema structure
     */
    public function parseSchema(string $content): ?array
    {
        try {
            // Try JSON first
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeSchema($data);
            }

            // Try YAML (requires symfony/yaml package, but we'll handle it gracefully)
            if (function_exists('yaml_parse')) {
                $data = yaml_parse($content);
                if ($data) {
                    return $this->normalizeSchema($data);
                }
            } else {
                // Try to parse YAML manually for basic cases
                $data = $this->parseYamlBasic($content);
                if ($data) {
                    return $this->normalizeSchema($data);
                }
            }

            Log::warning('Failed to parse schema: Invalid JSON or YAML');
            return null;
        } catch (\Exception $e) {
            Log::error('Error parsing schema', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Normalize schema structure (OpenAPI 3.0 or Swagger 2.0).
     */
    protected function normalizeSchema(array $schema): array
    {
        $normalized = [
            'version' => $this->detectVersion($schema),
            'paths' => [],
            'components' => [],
        ];

        // OpenAPI 3.0
        if (isset($schema['openapi'])) {
            $normalized['version'] = 'openapi3';
            $normalized['paths'] = $schema['paths'] ?? [];
            $normalized['components'] = $schema['components'] ?? [];
        }
        // Swagger 2.0
        elseif (isset($schema['swagger'])) {
            $normalized['version'] = 'swagger2';
            $normalized['paths'] = $schema['paths'] ?? [];
            $normalized['definitions'] = $schema['definitions'] ?? [];
        }

        return $normalized;
    }

    /**
     * Detect schema version.
     */
    protected function detectVersion(array $schema): string
    {
        if (isset($schema['openapi'])) {
            return 'openapi3';
        }
        if (isset($schema['swagger'])) {
            return 'swagger2';
        }
        return 'unknown';
    }

    /**
     * Basic YAML parser (for simple cases).
     */
    protected function parseYamlBasic(string $yaml): ?array
    {
        // This is a very basic YAML parser for simple cases
        // For production, use symfony/yaml package
        try {
            // Convert basic YAML to JSON-like structure
            $lines = explode("\n", $yaml);
            $result = [];
            $stack = [&$result];
            $indentStack = [0];

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                    continue;
                }

                $indent = strlen($line) - strlen(ltrim($line));
                
                // Pop stack until we find matching indent
                while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                    array_pop($indentStack);
                    array_pop($stack);
                }

                if (strpos($trimmed, ':') !== false) {
                    [$key, $value] = explode(':', $trimmed, 2);
                    $key = trim($key, '"\'');
                    $value = trim($value, ' "\'');
                    
                    if (empty($value)) {
                        $stack[count($stack) - 1][$key] = [];
                        $stack[] = &$stack[count($stack) - 1][$key];
                        $indentStack[] = $indent;
                    } else {
                        $stack[count($stack) - 1][$key] = $this->parseYamlValue($value);
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse YAML value.
     */
    protected function parseYamlValue(string $value)
    {
        $value = trim($value);
        
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        if (is_numeric($value)) return strpos($value, '.') !== false ? (float)$value : (int)$value;
        if (preg_match('/^\[.*\]$/', $value)) {
            return json_decode($value, true) ?? [];
        }
        
        return $value;
    }

    /**
     * Validate response against schema.
     *
     * @param string $responseBody
     * @param array $schema Parsed schema
     * @param string $path API path (e.g., /users)
     * @param string $method HTTP method
     * @param array $detectionRules
     * @return array ['valid' => bool, 'violations' => array]
     */
    public function validateResponse(string $responseBody, array $schema, string $path, string $method, array $detectionRules): array
    {
        $violations = [];

        try {
            $responseData = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['valid' => false, 'violations' => ['Response is not valid JSON']];
            }

            // Find schema definition for this path/method
            $schemaDef = $this->findSchemaDefinition($schema, $path, $method);
            if (!$schemaDef) {
                return ['valid' => true, 'violations' => []]; // No schema defined, skip validation
            }

            // Get expected response schema
            $expectedSchema = $this->getResponseSchema($schemaDef, 200); // Default to 200
            if (!$expectedSchema) {
                return ['valid' => true, 'violations' => []];
            }

            // Validate response structure
            $violations = array_merge(
                $violations,
                $this->checkMissingFields($responseData, $expectedSchema, $detectionRules),
                $this->checkTypeChanges($responseData, $expectedSchema, $detectionRules),
                $this->checkBreakingChanges($responseData, $expectedSchema, $detectionRules),
                $this->checkEnumViolations($responseData, $expectedSchema, $detectionRules)
            );

            return [
                'valid' => empty($violations),
                'violations' => $violations,
            ];
        } catch (\Exception $e) {
            Log::error('Error validating schema', ['error' => $e->getMessage()]);
            return ['valid' => false, 'violations' => ['Validation error: ' . $e->getMessage()]];
        }
    }

    /**
     * Find schema definition for path and method.
     */
    protected function findSchemaDefinition(array $schema, string $path, string $method): ?array
    {
        $paths = $schema['paths'] ?? [];
        $method = strtolower($method);

        // Try exact match first
        if (isset($paths[$path][$method])) {
            return $paths[$path][$method];
        }

        // Try path parameter matching (basic)
        foreach ($paths as $schemaPath => $methods) {
            $pattern = $this->pathToRegex($schemaPath);
            if (preg_match($pattern, $path) && isset($methods[$method])) {
                return $methods[$method];
            }
        }

        return null;
    }

    /**
     * Convert OpenAPI path pattern to regex.
     */
    protected function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Get response schema for status code.
     */
    protected function getResponseSchema(array $operation, int $statusCode): ?array
    {
        $responses = $operation['responses'] ?? [];
        
        if (isset($responses[$statusCode])) {
            return $this->extractSchemaFromResponse($responses[$statusCode]);
        }

        // Try default response
        if (isset($responses['default'])) {
            return $this->extractSchemaFromResponse($responses['default']);
        }

        return null;
    }

    /**
     * Extract schema from response definition.
     */
    protected function extractSchemaFromResponse(array $response): ?array
    {
        // OpenAPI 3.0
        if (isset($response['content']['application/json']['schema'])) {
            return $response['content']['application/json']['schema'];
        }

        // Swagger 2.0
        if (isset($response['schema'])) {
            return $response['schema'];
        }

        return null;
    }

    /**
     * Check for missing required fields.
     */
    protected function checkMissingFields(array $data, array $schema, array $rules): array
    {
        if (!$rules['detect_missing_fields'] ?? true) {
            return [];
        }

        $violations = [];
        $required = $schema['required'] ?? [];
        $properties = $schema['properties'] ?? [];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $violations[] = "Required field '{$field}' is missing";
            }
        }

        // Recursively check nested objects
        foreach ($properties as $field => $fieldSchema) {
            if (isset($data[$field]) && is_array($data[$field]) && isset($fieldSchema['type']) && $fieldSchema['type'] === 'object') {
                $nestedViolations = $this->checkMissingFields($data[$field], $fieldSchema, $rules);
                foreach ($nestedViolations as $violation) {
                    $violations[] = "{$field}.{$violation}";
                }
            }
        }

        return $violations;
    }

    /**
     * Check for type changes.
     */
    protected function checkTypeChanges(array $data, array $schema, array $rules): array
    {
        if (!$rules['detect_type_changes'] ?? true) {
            return [];
        }

        $violations = [];
        $properties = $schema['properties'] ?? [];

        foreach ($properties as $field => $fieldSchema) {
            if (!isset($data[$field])) {
                continue;
            }

            $expectedType = $fieldSchema['type'] ?? null;
            $actualType = $this->getPhpType($data[$field]);

            if ($expectedType && !$this->isTypeCompatible($actualType, $expectedType)) {
                $violations[] = "Field '{$field}' type changed from {$expectedType} to {$actualType}";
            }

            // Check nested objects
            if (is_array($data[$field]) && isset($fieldSchema['type']) && $fieldSchema['type'] === 'object') {
                $nestedViolations = $this->checkTypeChanges($data[$field], $fieldSchema, $rules);
                foreach ($nestedViolations as $violation) {
                    $violations[] = "{$field}.{$violation}";
                }
            }
        }

        return $violations;
    }

    /**
     * Check for breaking changes (new required fields).
     */
    protected function checkBreakingChanges(array $data, array $schema, array $rules): array
    {
        if (!$rules['detect_breaking_changes'] ?? true) {
            return [];
        }

        $violations = [];
        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        // Check if response has fields not in schema
        foreach ($data as $field => $value) {
            if (!isset($properties[$field])) {
                $violations[] = "New field '{$field}' appeared (breaking change)";
            }
        }

        return $violations;
    }

    /**
     * Check enum violations.
     */
    protected function checkEnumViolations(array $data, array $schema, array $rules): array
    {
        if (!$rules['detect_enum_violations'] ?? true) {
            return [];
        }

        $violations = [];
        $properties = $schema['properties'] ?? [];

        foreach ($properties as $field => $fieldSchema) {
            if (!isset($data[$field])) {
                continue;
            }

            if (isset($fieldSchema['enum'])) {
                if (!in_array($data[$field], $fieldSchema['enum'])) {
                    $violations[] = "Field '{$field}' value '{$data[$field]}' is not in allowed enum values: " . implode(', ', $fieldSchema['enum']);
                }
            }
        }

        return $violations;
    }

    /**
     * Get PHP type from value.
     */
    protected function getPhpType($value): string
    {
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'number';
        if (is_bool($value)) return 'boolean';
        if (is_array($value)) {
            // Check if it's a list or object
            return array_keys($value) === range(0, count($value) - 1) ? 'array' : 'object';
        }
        return 'string';
    }

    /**
     * Check if types are compatible.
     */
    protected function isTypeCompatible(string $actual, string $expected): bool
    {
        $compatibility = [
            'integer' => ['integer', 'number'],
            'number' => ['number', 'integer'],
            'string' => ['string'],
            'boolean' => ['boolean'],
            'array' => ['array'],
            'object' => ['object'],
        ];

        return in_array($expected, $compatibility[$actual] ?? []);
    }
}

