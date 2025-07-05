<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UserProfile.php';
require_once __DIR__ . '/BlogPost.php';
require_once __DIR__ . '/BlogController.php';
require_once __DIR__ . '/FileUploadExample.php';

use Koriym\FileUpload\FileUpload;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\BlogController;
use Ray\InputQuery\Demo\BlogPost;
use Ray\InputQuery\Demo\FileUploadExample;
use Ray\InputQuery\Demo\UserProfile;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\FileUploadFactoryInterface;
use Ray\InputQuery\InputQuery;

echo "=== Ray.InputQuery Demo ===\n\n";

// Setup dependency injection with FileUploadFactory
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind()->annotatedWith('app.version')->toInstance('1.0.0');
        $this->bind(FileUploadFactoryInterface::class)->to(FileUploadFactory::class);
    }
});

$inputQuery = new InputQuery($injector, new FileUploadFactory());

echo "1. Simple User Profile Creation\n";
echo "================================\n";

// Simulate form data for user profile
$userFormData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => '30',
    'bio' => 'Full-stack developer passionate about clean code',
    'isPublic' => '1',
];

$userProfile = $inputQuery->create(UserProfile::class, $userFormData);
echo $userProfile->getDisplayInfo() . "\n\n";

echo "2. Nested Object Creation (Blog Post with Author)\n";
echo "=================================================\n";

// Simulate form data with nested author information
$blogFormData = [
    'title' => 'Understanding Ray.InputQuery',
    'content' => 'Ray.InputQuery is a powerful library that transforms flat query data into type-safe PHP objects. It provides seamless integration with dependency injection and supports complex nested object creation...',
    'category' => 'Technology',
    'published' => '1',
    'authorName' => 'Jane Smith',
    'authorEmail' => 'jane@example.com',
    'authorId' => 'user123',
];

$blogPost = $inputQuery->create(BlogPost::class, $blogFormData);
echo $blogPost->getPostSummary() . "\n\n";

echo "3. Controller Method Arguments with DI\n";
echo "======================================\n";

// Test controller method with mixed Input and DI parameters
$controller = new BlogController();

// Blog post creation
$createMethod = new ReflectionMethod(BlogController::class, 'createPost');
$createArgs = $inputQuery->getArguments($createMethod, $blogFormData);
$result1 = $createMethod->invokeArgs($controller, $createArgs);
echo $result1 . "\n\n";

// Profile update
$updateMethod = new ReflectionMethod(BlogController::class, 'updateProfile');
$updateArgs = $inputQuery->getArguments($updateMethod, $userFormData);
$result2 = $updateMethod->invokeArgs($controller, $updateArgs);
echo $result2 . "\n\n";

echo "4. Scalar Type Conversions\n";
echo "==========================\n";

// Demonstrate automatic type conversion
$scalarData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'age' => '25',        // string -> int
    'isPublic' => 'true',  // string -> bool
];

$profile = $inputQuery->create(UserProfile::class, $scalarData);
echo "Converted types:\n";
echo "- age (string '25' -> int): " . var_export($profile->age, true) . ' (' . gettype($profile->age) . ")\n";
echo "- isPublic (string 'true' -> bool): " . var_export($profile->isPublic, true) . ' (' . gettype($profile->isPublic) . ")\n\n";

echo "5. Default Values\n";
echo "=================\n";

// Minimal data - other fields will use defaults
$minimalData = [
    'name' => 'Minimal User',
    'email' => 'minimal@example.com',
];

$minimalProfile = $inputQuery->create(UserProfile::class, $minimalData);
echo "Profile with defaults:\n";
echo $minimalProfile->getDisplayInfo() . "\n\n";

echo "6. Key Normalization (snake_case to camelCase)\n";
echo "===============================================\n";

// Form data with snake_case keys
$snakeCaseData = [
    'title' => 'Snake Case Test',
    'content' => 'Testing snake_case to camelCase conversion',
    'author_name' => 'Snake Author',     // author_name -> authorName
    'author_email' => 'snake@example.com', // author_email -> authorEmail
    'author_id' => 'snake123',           // author_id -> authorId
];

$snakeCasePost = $inputQuery->create(BlogPost::class, $snakeCaseData);
echo "Successfully converted snake_case keys:\n";
echo $snakeCasePost->getPostSummary() . "\n\n";

echo "7. File Upload with Factory Interface\n";
echo "=====================================\n";

// Simulate file upload data (normally from $_FILES)
$fileUploadData = [
    'title' => 'Project Files',
    'avatar' => FileUpload::create([
        'name' => 'profile.jpg',
        'type' => 'image/jpeg',
        'size' => 1024,
        'tmp_name' => '/tmp/upload1',
        'error' => 0,
    ]),
    'banner' => FileUpload::create([
        'name' => 'banner.png',
        'type' => 'image/png',
        'size' => 2048,
        'tmp_name' => '/tmp/upload2',
        'error' => 0,
    ]),
    'documents' => [
        FileUpload::create([
            'name' => 'spec.pdf',
            'type' => 'application/pdf',
            'size' => 5120,
            'tmp_name' => '/tmp/upload3',
            'error' => 0,
        ]),
        FileUpload::create([
            'name' => 'readme.txt',
            'type' => 'text/plain',
            'size' => 256,
            'tmp_name' => '/tmp/upload4',
            'error' => 0,
        ]),
    ],
];

$fileExample = $inputQuery->create(FileUploadExample::class, $fileUploadData);
echo $fileExample->getUploadSummary() . "\n";

echo "🌟 HTML Form Mapping Examples\n";
echo "=============================\n";
echo "<!-- Single file -->\n";
echo '<input type="file" name="avatar" required>' . "\n";
echo "↓ Maps to: #[InputFile] FileUpload \$avatar\n\n";

echo "<!-- Multiple files -->\n";
echo '<input type="file" name="documents[]" multiple>' . "\n";
echo "↓ Maps to: #[InputFile] array \$documents\n\n";

echo "<!-- Optional file -->\n";
echo '<input type="file" name="banner">' . "\n";
echo "↓ Maps to: #[InputFile] ?FileUpload \$banner = null\n\n";

echo "🏗️ Factory Pattern Benefits\n";
echo "===========================\n";
echo "✅ Clean dependency injection with FileUploadFactoryInterface\n";
echo "✅ Separated concerns: create() for single, createMultiple() for arrays\n";
echo "✅ Type-safe array keys with FileUploadKey (int|string)\n";
echo "✅ Optimized for BEAR.Resource compilation caching\n";
echo "✅ 100% test coverage maintained\n\n";

echo "Demo completed successfully! 🎉\n";
