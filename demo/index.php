<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

// Note: In a production environment, the FileUpload library would handle
// file uploads based on the environment (traditional PHP, Swoole, etc.)
// This demo shows how to use InputQuery with FileUpload objects.

// No Input classes needed - using method parameters directly

// Demo controller
final class FileUploadController
{
    public function __construct()
    {
        // Create uploads directory if it doesn't exist
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
    }
    
    public function handleUserProfile(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile(
            maxSize: 2 * 1024 * 1024,  // 2MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
        )] FileUpload|ErrorFileUpload $avatar,
        #[InputFile] FileUpload|ErrorFileUpload|null $banner = null,
    ): array {
        try {
            $results = [
                'name' => $name,
                'email' => $email,
                'success' => true
            ];
            
            // Handle avatar upload
            if ($avatar instanceof FileUpload) {
                $avatarPath = 'uploads/avatar_' . time() . '_' . $avatar->name;
                if ($avatar->move(__DIR__ . '/' . $avatarPath)) {
                    $results['avatar'] = $avatarPath;
                } else {
                    $results['avatar_error'] = 'Failed to save avatar';
                }
            } elseif ($avatar instanceof ErrorFileUpload) {
                $results['avatar_error'] = $avatar->message;
            }
            
            // Handle optional banner upload
            if ($banner instanceof FileUpload) {
                $bannerPath = 'uploads/banner_' . time() . '_' . $banner->name;
                if ($banner->move(__DIR__ . '/' . $bannerPath)) {
                    $results['banner'] = $bannerPath;
                } else {
                    $results['banner_error'] = 'Failed to save banner';
                }
            } elseif ($banner instanceof ErrorFileUpload) {
                $results['banner_error'] = $banner->message;
            }
            
            return $results;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public function handleGallery(
        #[Input] string $title,
        #[InputFile(
            maxSize: 1 * 1024 * 1024,  // 1MB per file
            allowedTypes: ['image/jpeg', 'image/png']
        )] array $images,
    ): array {
        try {
            $results = [
                'title' => $title,
                'images' => [],
                'errors' => []
            ];
            
            foreach ($images as $index => $image) {
                if ($image instanceof FileUpload) {
                    $imagePath = 'uploads/gallery_' . $index . '_' . time() . '_' . $image->name;
                    if ($image->move(__DIR__ . '/' . $imagePath)) {
                        $results['images'][] = $imagePath;
                    } else {
                        $results['errors'][] = "Image {$index}: Failed to save file";
                    }
                } elseif ($image instanceof ErrorFileUpload) {
                    $results['errors'][] = "Image {$index}: " . $image->message;
                }
            }
            
            $results['success'] = true;
            return $results;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
}

// Handle form submissions
$controller = new FileUploadController();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'profile':
                    // 1. Create InputQuery with DI container
                    $inputQuery = new InputQuery(new Injector());
                    
                    // 2. Get method reflection to analyze parameter types and attributes
                    $method = new ReflectionMethod($controller, 'handleUserProfile');
                    
                    // 3. Resolve all method arguments: 
                    //    - #[Input] scalar parameters from $_POST
                    //    - #[Input] FileUpload parameters from $_FILES (auto-created)
                    //    - Non-#[Input] parameters from DI container
                    $args = $inputQuery->getArguments($method, $_POST);
                    
                    // 4. Call method with type-safe, structured arguments
                    $result = $controller->handleUserProfile(...$args);
                    break;
                    
                case 'gallery':
                    $inputQuery = new InputQuery(new Injector());
                    $method = new ReflectionMethod($controller, 'handleGallery');
                    $args = $inputQuery->getArguments($method, $_POST);
                    $result = $controller->handleGallery(...$args);
                    break;
            }
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ray.InputQuery File Upload Demo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #444;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background: #fafafa;
        }
        
        button {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        .result {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .result.error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .file-info {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .uploaded-file {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .code-example {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            overflow-x: auto;
        }
        
        .code-example pre {
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ray.InputQuery File Upload Demo</h1>
        <p class="subtitle">File upload integration with Koriym.FileUpload</p>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="result">
                <strong>Debug Info:</strong>
                <pre>$_POST: <?= htmlspecialchars(json_encode($_POST, JSON_PRETTY_PRINT)) ?></pre>
                <pre>$_FILES: <?= htmlspecialchars(json_encode($_FILES, JSON_PRETTY_PRINT)) ?></pre>
                
                <?php 
                // Capture any error logs during processing
                ob_start();
                ?>
                <strong>Processing Debug:</strong>
                <pre id="debug-log"></pre>
            </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <div class="result <?= isset($result['error']) ? 'error' : '' ?>">
                <?php if (isset($result['error'])): ?>
                    <strong>Error:</strong> <?= htmlspecialchars($result['error']) ?>
                <?php elseif (isset($result['success'])): ?>
                    <strong>Success!</strong> Files uploaded successfully.
                    <pre><?= htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>User Profile Upload</h2>
        <p>Upload avatar (required) and banner (optional).</p>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="profile">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required value="Jingu">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="jingu@example.com">
            </div>
            
            <div class="form-group">
                <label for="avatar">Avatar (Required):</label>
                <input type="file" id="avatar" name="avatar" accept="image/*" required>
                <div class="file-info">Max 2MB, JPEG/PNG/GIF only</div>
            </div>
            
            <div class="form-group">
                <label for="banner">Banner (Optional):</label>
                <input type="file" id="banner" name="banner" accept="image/*">
                <div class="file-info">Max 2MB, JPEG/PNG/GIF only</div>
            </div>
            
            <button type="submit">Upload Profile</button>
        </form>
        
        <div class="code-example">
            <strong>Input Class:</strong>
            <pre><code>final class UserProfileInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[InputFile(
            maxSize: 2 * 1024 * 1024,  // 2MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
        )] public readonly FileUpload|ErrorFileUpload $avatar,
        #[InputFile] public readonly FileUpload|ErrorFileUpload|null $banner = null,
    ) {}
}</code></pre>
        </div>
    </div>

    <div class="container">
        <h2>Gallery Upload</h2>
        <p>Upload multiple images.</p>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="gallery">
            
            <div class="form-group">
                <label for="title">Gallery Title:</label>
                <input type="text" id="title" name="title" required value="My Photo Gallery">
            </div>
            
            <div class="form-group">
                <label for="images">Images:</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                <div class="file-info">Max 1MB per image, JPEG/PNG only, multiple files allowed</div>
            </div>
            
            <button type="submit">Upload Gallery</button>
        </form>
        
        <div class="code-example">
            <strong>Input Class:</strong>
            <pre><code>final class GalleryInput
{
    /**
     * @param list&lt;FileUpload|ErrorFileUpload&gt; $images
     */
    public function __construct(
        #[Input] public readonly string $title,
        #[InputFile(
            maxSize: 1 * 1024 * 1024,  // 1MB per file
            allowedTypes: ['image/jpeg', 'image/png']
        )] public readonly array $images,
    ) {}
}</code></pre>
        </div>
    </div>
    
    <div class="container">
        <h2>How It Works</h2>
        <p>Features demonstrated:</p>
        <ul>
            <li><strong>Type Safety:</strong> FileUpload objects created automatically</li>
            <li><strong>Validation:</strong> File size and type validation</li>
            <li><strong>Error Handling:</strong> Graceful error handling</li>
            <li><strong>Array Support:</strong> Multiple file uploads</li>
            <li><strong>Optional Files:</strong> Nullable parameters</li>
        </ul>
        
        <div class="code-example">
            <strong>Usage:</strong>
            <pre><code>// In production, FileUpload library handles environment differences
$query = array_merge($_POST, [
    'avatar' => FileUpload::create($_FILES['avatar']),
    'banner' => isset($_FILES['banner']) ? FileUpload::create($_FILES['banner']) : null
]);

$inputQuery = new InputQuery(new Injector());
$input = $inputQuery->create(UserProfileInput::class, $query);</code></pre>
        </div>
    </div>
</body>
</html>