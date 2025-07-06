# File Upload Integration Design

## Overview

This document outlines the design for integrating Koriym.FileUpload with Ray.InputQuery to provide seamless file upload handling alongside form data processing. The goal is to enable declarative file upload handling using the same `#[Input]` attribute pattern.

## Problem Statement

Currently, Ray.InputQuery handles flat key-value data transformation but does not process `$_FILES` directly. File uploads require separate handling, breaking the unified declarative approach that Ray.InputQuery provides for other form data.

## Proposed Solution

### Vision

```php
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;

final class UserProfileInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input(fileOptions: [
            'maxSize' => 5 * 1024 * 1024,
            'allowedTypes' => ['image/jpeg', 'image/png']
        ])] public readonly FileUpload $avatar,
        #[Input] public readonly ?FileUpload $banner = null  // Optional file
    ) {}
}

// Usage
$inputQuery = new InputQuery($injector, $_FILES);  // Pass $_FILES
$input = $inputQuery->create(UserProfileInput::class, $_POST);
```

### HTML Form Structure

```html
<form method="post" enctype="multipart/form-data">
    <input name="name" value="Jingu">
    <input name="email" value="jingu@example.com">
    <input name="avatar" type="file" accept="image/*" required>
    <input name="banner" type="file" accept="image/*">
    <button type="submit">Submit</button>
</form>
```

## Implementation Design

### 1. Enhanced Input Attribute

```php
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
    public function __construct(
        public readonly string|null $item = null,
        public readonly array|null $fileOptions = null,
    ) {}
}
```

### 2. InputQuery Constructor Enhancement

```php
final class InputQuery implements InputQueryInterface
{
    public function __construct(
        private InjectorInterface $injector,
        private array $files = [],  // $_FILES data
    ) {}
}
```

### 3. File Upload Detection Logic

```php
private function resolveObjectType(
    ReflectionParameter $param, 
    array $query, 
    array $inputAttributes, 
    ReflectionNamedType $type
): mixed {
    $paramName = $param->getName();
    $className = $type->getName();
    
    // Check for FileUpload type
    if ($className === FileUpload::class || is_subclass_of($className, FileUpload::class)) {
        return $this->resolveFileUpload($param, $inputAttributes);
    }
    
    // Existing object resolution logic...
}

private function resolveFileUpload(
    ReflectionParameter $param, 
    array $inputAttributes
): FileUpload|ErrorFileUpload {
    $paramName = $param->getName();
    
    if (!array_key_exists($paramName, $this->files)) {
        if ($param->allowsNull() || $param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        throw new InvalidArgumentException("Required file parameter '{$paramName}' is missing");
    }
    
    $fileData = $this->files[$paramName];
    $inputAttribute = $inputAttributes[0]->newInstance();
    $fileOptions = $inputAttribute->fileOptions ?? [];
    
    return FileUpload::create($fileData, $fileOptions);
}
```

### 4. Array File Upload Support

```php
/**
 * @param list<FileUpload> $images
 */
public function uploadGallery(
    #[Input(item: FileUpload::class, fileOptions: [
        'maxSize' => 2 * 1024 * 1024,
        'allowedTypes' => ['image/jpeg', 'image/png']
    ])] array $images
) {
    foreach ($images as $image) {
        $image->move('./uploads/' . $image->name);
    }
}
```

HTML:
```html
<form method="post" enctype="multipart/form-data">
    <input name="images[]" type="file" multiple accept="image/*">
    <button type="submit">Upload Gallery</button>
</form>
```

## Implementation Considerations

### 1. Dependency Management

- Add `koriym/file-upload` as an optional dependency
- Use interface detection to enable FileUpload features only when the library is available
- Provide graceful degradation when FileUpload is not installed

```php
private function isFileUploadAvailable(): bool
{
    return class_exists(FileUpload::class);
}
```

### 2. Error Handling

```php
// Return ErrorFileUpload for validation failures
if ($upload instanceof ErrorFileUpload) {
    if ($param->allowsNull()) {
        return null;
    }
    throw new InvalidArgumentException("File upload error: " . $upload->message);
}
```

### 3. Testing Strategy

```php
// In tests, use FileUpload::fromFile() for easy testing
$upload = FileUpload::fromFile(__DIR__ . '/fixtures/test-image.jpg');
$input = $inputQuery->newInstance(UserProfileInput::class, [
    'name' => 'Test User',
    'email' => 'test@example.com'
], ['avatar' => $upload->toArray()]);
```

### 4. Backward Compatibility

- Maintain existing constructor signature by making `$files` parameter optional
- Existing code without file uploads continues to work unchanged
- New file upload features are opt-in

## Benefits

### 1. Unified Declarative Approach
- File uploads use the same `#[Input]` attribute pattern
- Consistent validation and error handling
- Type-safe file handling

### 2. Enhanced Developer Experience
- No need to manually process `$_FILES`
- Built-in validation through fileOptions
- Seamless integration with existing form processing

### 3. Framework Integration
- Works naturally with BEAR.Resource
- Maintains Ray.InputQuery's design philosophy
- Leverages existing DI and attribute infrastructure

## Migration Path

### Phase 1: Core Integration
1. Add optional FileUpload dependency
2. Enhance Input attribute with fileOptions
3. Implement basic FileUpload resolution
4. Add comprehensive tests

### Phase 2: Advanced Features
1. Array file upload support
2. Custom FileUpload subclass support
3. Enhanced error handling and validation
4. Performance optimizations

### Phase 3: Documentation and Examples
1. Update README with file upload examples
2. Create demo applications
3. Add to sample data generators
4. Integration guides for BEAR.Resource

## Technical Challenges

### 1. Multiple Data Sources
- Handling both `$_POST` and `$_FILES` data
- Maintaining separation of concerns
- Consistent parameter resolution

### 2. Validation Timing
- FileUpload validation occurs during object creation
- May need to delay validation for better error reporting
- Integration with form validation frameworks

### 3. Memory Management
- Large file uploads and memory usage
- Streaming capabilities for large files
- Cleanup of temporary files

## Alternative Approaches Considered

### 1. Separate FileInputQuery Class
- **Pros**: Clear separation, no API changes
- **Cons**: Breaks unified approach, requires separate handling

### 2. FileUpload as Regular Objects
- **Pros**: Simple implementation
- **Cons**: Loses FileUpload's validation and security features

### 3. Custom File Attribute
- **Pros**: Dedicated file handling
- **Cons**: Inconsistent with existing Input attribute pattern

## Koriym.FileUpload Quality Assessment

### Code Quality Evaluation: **A+**

After thorough analysis of the Koriym.FileUpload source code, the library demonstrates exceptional quality and architectural alignment with Ray.InputQuery.

#### Strengths

**1. Type Safety Excellence**
- Comprehensive Psalm type annotations (`@psalm-type UploadedFile`, `@psalm-immutable`)
- Strict type checking with `declare(strict_types=1)`
- Static analysis optimized codebase

**2. Immutable Design Philosophy**
- `@psalm-immutable` annotations ensure side-effect-free operations
- Predictable behavior and thread safety
- Aligns perfectly with Ray.InputQuery's philosophy

**3. Robust Factory Pattern**
- `FileUpload::create()` provides controlled object instantiation
- Returns `ErrorFileUpload` on validation failure instead of throwing exceptions
- Consistent error handling through return values

**4. Environment Adaptability**
- Web environment: `move_uploaded_file()` for security
- CLI environment: `rename()` for testing compatibility
- `fromFile()` method specifically designed for test scenarios

**5. Comprehensive Error Handling**
- Proper handling of PHP's standard upload error codes
- Human-readable error messages
- Unified error representation through `ErrorFileUpload`

#### Integration Compatibility

**1. Architectural Harmony**
- ✅ Shared immutable design philosophy
- ✅ Type safety as core principle  
- ✅ Error handling through return values vs exceptions
- ✅ Factory pattern usage

**2. Seamless Integration Points**
```php
// Type detection in Ray.InputQuery
if ($className === FileUpload::class) {
    return FileUpload::create($this->files[$paramName], $fileOptions);
}

// Unified error handling
if ($upload instanceof ErrorFileUpload) {
    // Handle validation errors consistently
}
```

**3. Testing Integration**

```php
// Natural testing approach
$testUpload = FileUpload::fromFile(__DIR__ . '/fixtures/test.jpg');
$input = $inputQuery->newInstance(UserInput::class, $_POST, [
    'avatar' => $testUpload->toArray()
]);
```

#### Integration Benefits

**1. Consistent Developer Experience**
- Same quality standards as Ray.InputQuery
- Identical design principles (immutable, type-safe)
- Matching error handling patterns

**2. Implementation Simplicity**
- Natural integration with existing code patterns
- No complex transformation layers required
- Concise test code

**3. Security by Design**
- Proper `move_uploaded_file()` usage
- Built-in validation capabilities
- Type safety prevents unexpected behaviors

### Technical Recommendations

**Implementation Priority**
```php
// High Priority: Basic integration
#[Input] public readonly FileUpload $avatar

// Medium Priority: Validation options
#[Input(fileOptions: ['maxSize' => 1024*1024])] 
public readonly FileUpload $avatar

// Lower Priority: Array support
#[Input(item: FileUpload::class)] 
public readonly array $images
```

**Potential Enhancements**
- Helper methods for array upload processing
- Enhanced validation option integration with Ray.InputQuery
- Custom FileUpload subclass support optimization

## Conclusion

The proposed integration maintains Ray.InputQuery's declarative philosophy while adding powerful file upload capabilities. By leveraging Koriym.FileUpload's type-safe approach and Ray.InputQuery's attribute-based design, developers can handle complex forms with both regular data and file uploads using a unified, declarative interface.

**Quality Assessment Conclusion**: Koriym.FileUpload exceeds expectations with exceptional code quality, making it an ideal integration candidate. The architectural alignment between both libraries ensures that the proposed integration will deliver a groundbreaking developer experience for PHP file upload processing.

This design preserves backward compatibility while opening up new possibilities for web application development in the Ray ecosystem.
