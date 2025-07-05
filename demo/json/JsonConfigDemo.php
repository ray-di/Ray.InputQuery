<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use JsonException;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Throwable;

use function array_merge_recursive;
use function file_get_contents;
use function implode;
use function in_array;
use function is_int;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * JSON Configuration Demo
 *
 * Demonstrates how Ray.InputQuery can handle JSON configuration file uploads
 * with validation and schema checking.
 */
final class JsonConfigDemo
{
    public function __construct(
        #[InputFile(
            allowedExtensions: ['json'],
            allowedTypes: ['application/json', 'text/json', 'text/plain'],
            maxSize: 5 * 1024 * 1024, // 5MB
        )]
        public readonly FileUpload $configFile,
        #[Input]
        public readonly bool $validateSchema = true,
        #[Input]
        public readonly bool $mergeWithDefaults = true,
        #[Input]
        public readonly string $environment = 'production',
    ) {
    }

    public function processConfig(): array
    {
        $results = [
            'success' => true,
            'config' => [],
            'warnings' => [],
            'errors' => [],
        ];

        try {
            $jsonContent = file_get_contents($this->configFile->tmp_name);
            $config = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

            if ($this->validateSchema) {
                $validation = $this->validateConfigSchema($config);
                if (! $validation['valid']) {
                    $results['success'] = false;
                    $results['errors'] = $validation['errors'];

                    return $results;
                }

                $results['warnings'] = $validation['warnings'];
            }

            if ($this->mergeWithDefaults) {
                $config = $this->mergeWithDefaultConfig($config);
            }

            $results['config'] = $config;
            $results['file_info'] = [
                'name' => $this->configFile->name,
                'size' => $this->configFile->size,
                'environment' => $this->environment,
                'validate_schema' => $this->validateSchema,
                'merge_defaults' => $this->mergeWithDefaults,
            ];
        } catch (JsonException $e) {
            $results['success'] = false;
            $results['errors'][] = 'Invalid JSON format: ' . $e->getMessage();
        } catch (Throwable $e) {
            $results['success'] = false;
            $results['errors'][] = 'Configuration processing error: ' . $e->getMessage();
        }

        return $results;
    }

    private function validateConfigSchema(array $config): array
    {
        $errors = [];
        $warnings = [];

        // Required fields
        $requiredFields = ['app_name', 'database', 'logging'];
        foreach ($requiredFields as $field) {
            if (! isset($config[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Database configuration validation
        if (isset($config['database'])) {
            $dbConfig = $config['database'];
            $requiredDbFields = ['host', 'database', 'username'];
            foreach ($requiredDbFields as $field) {
                if (! isset($dbConfig[$field])) {
                    $errors[] = "Missing required database field: {$field}";
                }
            }

            if (isset($dbConfig['port']) && (! is_int($dbConfig['port']) || $dbConfig['port'] < 1 || $dbConfig['port'] > 65535)) {
                $errors[] = 'Invalid database port: must be between 1 and 65535';
            }
        }

        // Logging configuration validation
        if (isset($config['logging'])) {
            $logConfig = $config['logging'];
            $validLevels = ['debug', 'info', 'warning', 'error', 'critical'];
            if (isset($logConfig['level']) && ! in_array($logConfig['level'], $validLevels)) {
                $errors[] = 'Invalid log level: must be one of ' . implode(', ', $validLevels);
            }
        }

        // Environment-specific validation
        if ($this->environment === 'production') {
            if (isset($config['debug']) && $config['debug'] === true) {
                $warnings[] = 'Debug mode is enabled in production environment';
            }

            if (isset($config['logging']['level']) && $config['logging']['level'] === 'debug') {
                $warnings[] = 'Debug logging enabled in production environment';
            }
        }

        // Check for deprecated fields
        $deprecatedFields = ['old_database_url', 'legacy_cache_driver'];
        foreach ($deprecatedFields as $field) {
            if (isset($config[$field])) {
                $warnings[] = "Deprecated field found: {$field}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function mergeWithDefaultConfig(array $config): array
    {
        $defaults = [
            'app_name' => 'My Application',
            'debug' => false,
            'timezone' => 'UTC',
            'database' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'cache' => [
                'driver' => 'file',
                'ttl' => 3600,
            ],
            'logging' => [
                'level' => 'info',
                'driver' => 'file',
                'path' => '/var/log/app.log',
            ],
            'mail' => [
                'driver' => 'smtp',
                'host' => 'localhost',
                'port' => 587,
                'encryption' => 'tls',
            ],
        ];

        return array_merge_recursive($defaults, $config);
    }

    public function getSummary(): string
    {
        $results = $this->processConfig();

        $summary = "⚙️ JSON Configuration Summary\n";
        $summary .= "=============================\n";
        $summary .= "File: {$this->configFile->name} ({$this->configFile->size} bytes)\n";
        $summary .= "Environment: {$this->environment}\n";
        $summary .= 'Schema Validation: ' . ($this->validateSchema ? 'Enabled' : 'Disabled') . "\n";
        $summary .= 'Merge Defaults: ' . ($this->mergeWithDefaults ? 'Yes' : 'No') . "\n";
        $summary .= 'Status: ' . ($results['success'] ? '✅ Valid' : '❌ Invalid') . "\n\n";

        if ($results['success']) {
            $config = $results['config'];
            $summary .= "📋 Configuration Overview:\n";
            $summary .= '  App Name: ' . ($config['app_name'] ?? 'Not set') . "\n";
            $summary .= '  Debug Mode: ' . ($config['debug'] ? 'Enabled' : 'Disabled') . "\n";
            $summary .= '  Timezone: ' . ($config['timezone'] ?? 'Not set') . "\n";

            if (isset($config['database'])) {
                $db = $config['database'];
                $summary .= "  Database: {$db['driver']}://{$db['host']}:{$db['port']}/{$db['database']}\n";
            }

            if (isset($config['logging'])) {
                $log = $config['logging'];
                $summary .= "  Logging: {$log['level']} level via {$log['driver']}\n";
            }
        }

        if (! empty($results['warnings'])) {
            $summary .= "\n⚠️ Warnings:\n";
            foreach ($results['warnings'] as $warning) {
                $summary .= "  • {$warning}\n";
            }
        }

        if (! empty($results['errors'])) {
            $summary .= "\n❌ Errors:\n";
            foreach ($results['errors'] as $error) {
                $summary .= "  • {$error}\n";
            }
        }

        return $summary;
    }
}
