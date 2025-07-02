<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UserProfile.php';
require_once __DIR__ . '/BlogPost.php';
require_once __DIR__ . '/BlogController.php';

use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\BlogController;
use Ray\InputQuery\Demo\BlogPost;
use Ray\InputQuery\Demo\UserProfile;
use Ray\InputQuery\InputQuery;

echo "=== Ray.InputQuery Demo ===\n\n";

// Setup dependency injection
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind()->annotatedWith('app.version')->toInstance('1.0.0');
    }
});

$inputQuery = new InputQuery($injector);

echo "1. Simple User Profile Creation\n";
echo "================================\n";

// Simulate form data for user profile
$userFormData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => '30',
    'bio' => 'Full-stack developer passionate about clean code',
    'isPublic' => '1'
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
    'authorId' => 'user123'
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
    'isPublic' => 'true'  // string -> bool
];

$profile = $inputQuery->create(UserProfile::class, $scalarData);
echo "Converted types:\n";
echo "- age (string '25' -> int): " . var_export($profile->age, true) . " (" . gettype($profile->age) . ")\n";
echo "- isPublic (string 'true' -> bool): " . var_export($profile->isPublic, true) . " (" . gettype($profile->isPublic) . ")\n\n";

echo "5. Default Values\n";
echo "=================\n";

// Minimal data - other fields will use defaults
$minimalData = [
    'name' => 'Minimal User',
    'email' => 'minimal@example.com'
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
    'author_id' => 'snake123'           // author_id -> authorId
];

$snakeCasePost = $inputQuery->create(BlogPost::class, $snakeCaseData);
echo "Successfully converted snake_case keys:\n";
echo $snakeCasePost->getPostSummary() . "\n\n";

echo "Demo completed successfully! 🎉\n";
