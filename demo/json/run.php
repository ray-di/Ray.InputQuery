<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/JsonConfigDemo.php';

use Koriym\FileUpload\FileUpload;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\JsonConfigDemo;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\FileUploadFactoryInterface;
use Ray\InputQuery\InputQuery;

echo "=== JSON Configuration Demo ===\n\n";

// Setup dependency injection
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(FileUploadFactoryInterface::class)->to(FileUploadFactory::class);
    }
});

$inputQuery = new InputQuery($injector, new FileUploadFactory());

// Create mock JSON file upload
$jsonFile = FileUpload::create([
    'name' => 'config.json',
    'type' => 'application/json',
    'size' => filesize(__DIR__ . '/config.json'),
    'tmp_name' => __DIR__ . '/config.json',
    'error' => 0,
]);

echo "📂 Testing with sample JSON config: config.json\n";
echo 'File size: ' . number_format($jsonFile->size) . " bytes\n\n";

// Demo 1: Production environment with validation
echo "1. Production Environment (Schema Validation + Merge Defaults)\n";
echo "==============================================================\n";

$jsonDemo1 = $inputQuery->create(JsonConfigDemo::class, [
    'configFile' => $jsonFile,
    'validateSchema' => true,
    'mergeWithDefaults' => true,
    'environment' => 'production',
]);

echo $jsonDemo1->getSummary();
echo "\n";

// Demo 2: Development environment without strict validation
echo "2. Development Environment (No Schema Validation)\n";
echo "==================================================\n";

$jsonDemo2 = $inputQuery->create(JsonConfigDemo::class, [
    'configFile' => $jsonFile,
    'validateSchema' => false,
    'mergeWithDefaults' => false,
    'environment' => 'development',
]);

echo $jsonDemo2->getSummary();
echo "\n";

// Demo 3: Test with invalid JSON
echo "3. Error Handling Demo (Invalid JSON)\n";
echo "======================================\n";

// Create invalid JSON file
$invalidJson = '{"app_name": "Test App", "debug": true, invalid_json_here}';
file_put_contents(__DIR__ . '/invalid_config.json', $invalidJson);

$invalidFile = FileUpload::create([
    'name' => 'invalid_config.json',
    'type' => 'application/json',
    'size' => strlen($invalidJson),
    'tmp_name' => __DIR__ . '/invalid_config.json',
    'error' => 0,
]);

$jsonDemo3 = $inputQuery->create(JsonConfigDemo::class, [
    'configFile' => $invalidFile,
    'validateSchema' => true,
    'environment' => 'production',
]);

echo $jsonDemo3->getSummary();
echo "\n";

// Demo 4: Test with incomplete configuration
echo "4. Schema Validation Demo (Missing Required Fields)\n";
echo "====================================================\n";

// Create incomplete JSON config
$incompleteConfig = [
    'app_name' => 'Incomplete App',
    'debug' => true,
    // Missing database and logging sections
    'cache' => ['driver' => 'file'],
];
file_put_contents(__DIR__ . '/incomplete_config.json', json_encode($incompleteConfig, JSON_PRETTY_PRINT));

$incompleteFile = FileUpload::create([
    'name' => 'incomplete_config.json',
    'type' => 'application/json',
    'size' => filesize(__DIR__ . '/incomplete_config.json'),
    'tmp_name' => __DIR__ . '/incomplete_config.json',
    'error' => 0,
]);

$jsonDemo4 = $inputQuery->create(JsonConfigDemo::class, [
    'configFile' => $incompleteFile,
    'validateSchema' => true,
    'mergeWithDefaults' => true,
    'environment' => 'production',
]);

echo $jsonDemo4->getSummary();
echo "\n";

// Demo 5: Test with production warnings
echo "5. Production Warnings Demo (Debug Mode in Production)\n";
echo "=======================================================\n";

// Create config with production warnings
$warningConfig = [
    'app_name' => 'Warning App',
    'debug' => true,  // This will trigger a warning in production
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'test_db',
        'username' => 'test_user',
    ],
    'logging' => [
        'level' => 'debug',  // This will also trigger a warning in production
        'driver' => 'file',
    ],
    'old_database_url' => 'mysql://old_connection',  // Deprecated field
];
file_put_contents(__DIR__ . '/warning_config.json', json_encode($warningConfig, JSON_PRETTY_PRINT));

$warningFile = FileUpload::create([
    'name' => 'warning_config.json',
    'type' => 'application/json',
    'size' => filesize(__DIR__ . '/warning_config.json'),
    'tmp_name' => __DIR__ . '/warning_config.json',
    'error' => 0,
]);

$jsonDemo5 = $inputQuery->create(JsonConfigDemo::class, [
    'configFile' => $warningFile,
    'validateSchema' => true,
    'mergeWithDefaults' => true,
    'environment' => 'production',
]);

echo $jsonDemo5->getSummary();

// Demo 6: Show detailed configuration processing
echo "\n6. Detailed Configuration Processing\n";
echo "====================================\n";

$results = $jsonDemo1->processConfig();
if ($results['success']) {
    echo "✅ Configuration processed successfully!\n\n";

    echo "📋 Complete Configuration Overview:\n";
    $config = $results['config'];

    echo "🏷️  Application:\n";
    echo "  Name: {$config['app_name']}\n";
    echo '  Debug: ' . ($config['debug'] ? 'Enabled' : 'Disabled') . "\n";
    echo "  Timezone: {$config['timezone']}\n\n";

    echo "🗄️  Database:\n";
    $db = $config['database'];
    echo "  Driver: {$db['driver']}\n";
    echo "  Host: {$db['host']}:{$db['port']}\n";
    echo "  Database: {$db['database']}\n";
    echo "  Username: {$db['username']}\n";
    echo "  Charset: {$db['charset']}\n\n";

    echo "📝 Logging:\n";
    $log = $config['logging'];
    echo "  Level: {$log['level']}\n";
    echo "  Driver: {$log['driver']}\n";
    echo "  Path: {$log['path']}\n\n";

    echo "🚀 Features:\n";
    foreach ($config['features'] as $feature => $enabled) {
        $status = $enabled ? '✅ Enabled' : '❌ Disabled';
        echo '  ' . ucwords(str_replace('_', ' ', $feature)) . ": {$status}\n";
    }
}

// Cleanup temporary files
@unlink(__DIR__ . '/invalid_config.json');
@unlink(__DIR__ . '/incomplete_config.json');
@unlink(__DIR__ . '/warning_config.json');

echo "\n🎉 JSON Configuration Demo completed successfully!\n";
echo "\n💡 Key Features Demonstrated:\n";
echo "  ✅ Type-safe JSON configuration handling with Ray.InputQuery\n";
echo "  ✅ Schema validation with detailed error reporting\n";
echo "  ✅ Environment-specific configuration warnings\n";
echo "  ✅ Default value merging for missing fields\n";
echo "  ✅ Comprehensive error handling for invalid JSON\n";
echo "  ✅ Production vs Development environment differences\n";
echo "  ✅ File upload integration with Koriym.FileUpload\n";
