# Ray.InputQuery

[![Continuous Integration](https://github.com/ray-di/Ray.InputQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.InputQuery/actions/workflows/continuous-integration.yml)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.InputQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.InputQuery)
[![codecov](https://codecov.io/gh/ray-di/Ray.InputQuery/branch/main/graph/badge.svg)](https://codecov.io/gh/ray-di/Ray.InputQuery)

Structured input objects from HTTP with 100% test coverage.

## Overview

Ray.InputQuery transforms flat HTTP data into structured PHP objects through explicit type declarations. Using the `#[Input]` attribute, you declare which parameters come from query data, while other parameters are resolved via dependency injection.

**Core Mechanism:**
- **Attribute-Based Control** - `#[Input]` explicitly marks query-sourced parameters
- **Prefix-Based Nesting** - `assigneeId`, `assigneeName` fields automatically compose `UserInput` objects
- **Type-Safe Conversion** - Leverages PHP's type system for automatic scalar conversion
- **DI Integration** - Parameters without `#[Input]` are resolved from dependency injection

**The Problem:**
```php
// Manual parameter extraction and object construction
$data = $request->getParsedBody(); // or $_POST
$title = $data['title'] ?? '';
$assigneeId = $data['assigneeId'] ?? '';
$assigneeName = $data['assigneeName'] ?? '';
$assigneeEmail = $data['assigneeEmail'] ?? '';
```

**Ray.InputQuery Solution:**
```php
// Declarative structure definition
final class TodoInput {
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly UserInput $assignee,  // Auto-composed from assigneeId, assigneeName, assigneeEmail
        private LoggerInterface $logger  // From DI container
    ) {}
}

public function createTodo(TodoInput $input) {
    // $input automatically structured from request data
}
```

## Installation

```bash
composer require ray/input-query
```

## Demo

To see file upload integration in action:

```bash
php -S localhost:8080 -t demo/
```

Then visit [http://localhost:8080](http://localhost:8080) in your browser.

## Documentation

Comprehensive documentation including design philosophy, AI prompts for development assistance, and sample data examples can be found in the [docs/](docs/) directory.

### Framework Integration

For framework-specific integration examples, see the **[Framework Integration Guide](docs/framework_integration.md)** which covers:

- Laravel, Symfony, CakePHP, Yii Framework 1.x, BEAR.Sunday, and Slim Framework
- Three usage patterns (Reflection, Direct Object Creation, Spread Operator)
- Testing examples and best practices

## Usage

Ray.InputQuery converts flat query data into typed PHP objects automatically.

### Basic Usage

Define your input class with the `#[Input]` attribute on parameters that come from query data:

```php
use Ray\InputQuery\Attribute\Input;

final class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email
    ) {}
}
```

Create input objects from query data:

```php
use Ray\InputQuery\InputQuery;
use Ray\Di\Injector;

$injector = new Injector();
$inputQuery = new InputQuery($injector);

// Create object directly from array
$user = $inputQuery->newInstance(UserInput::class, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

echo $user->name;  // John Doe
echo $user->email; // john@example.com

// Method argument resolution from $_POST
$method = new ReflectionMethod(UserController::class, 'register');
$args = $inputQuery->getArguments($method, $_POST);
$result = $method->invokeArgs($controller, $args);

 // Or with PSR-7 Request
$args = $inputQuery->getArguments($method, $request->getParsedBody());
$result = $method->invokeArgs($controller, $args);
```

### Nested Objects

Ray.InputQuery automatically creates nested objects from flat query data:

```php
final class AddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly string $zip
    ) {}
}

final class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly AddressInput $address  // Nested input
    ) {}
}

$user = $inputQuery->newInstance(UserInput::class, [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'addressStreet' => '123 Main St',
    'addressCity' => 'Tokyo',
    'addressZip' => '100-0001'
]);

echo $user->name;            // John Doe
echo $user->address->street; // 123 Main St
```

### Array Support

Ray.InputQuery supports arrays and ArrayObject collections with the `item` parameter:

```php
use Ray\InputQuery\Attribute\Input;

final class UserInput
{
    public function __construct(
        #[Input] public readonly string $id,
        #[Input] public readonly string $name
    ) {}
}

final class UserListController
{
    /**
     * @param list<UserInput> $users
     */
    public function updateUsers(
        #[Input(item: UserInput::class)] array $users  // Array of UserInput objects
    ) {
        foreach ($users as $user) {
            echo $user->name; // Each element is a UserInput instance
        }
    }
    
    /**
     * @param ArrayObject<int, UserInput> $users
     */
    public function processUsers(
        #[Input(item: UserInput::class)] ArrayObject $users  // ArrayObject collection
    ) {
        // $users is an ArrayObject containing UserInput instances
    }
}
```

#### Query data format for arrays

Arrays should be submitted as indexed arrays. Here's how to structure HTML forms and the resulting data:

```html
<!-- HTML Form -->
<form method="post">
    <input name="users[0][id]" value="1">
    <input name="users[0][name]" value="Jingu">
    
    <input name="users[1][id]" value="2">
    <input name="users[1][name]" value="Horikawa">
</form>

```

This will be received as:

```php
$data = [
    'users' => [
        ['id' => '1', 'name' => 'Jingu'],
        ['id' => '2', 'name' => 'Horikawa']
    ]
];

$result = $method->invokeArgs($controller, $inputQuery->getArguments($method, $data));
// Arguments automatically resolved as UserInput objects
```

#### Simple array values (e.g., checkboxes)

For simple arrays like checkboxes or multi-select:

```html
<form method="post">
    <!-- Checkbox group -->
    <input name="hobbies[]" type="checkbox" value="music">
    <input name="hobbies[]" type="checkbox" value="sports">
    <input name="hobbies[]" type="checkbox" value="reading">
    
    <!-- Multi-select -->
    <select name="categories[]" multiple>
        <option value="tech">Technology</option>
        <option value="business">Business</option>
        <option value="lifestyle">Lifestyle</option>
    </select>
</form>
```

This will be received as:

```php
$data = [
    'hobbies' => ['music', 'sports'],      // Only checked values
    'categories' => ['tech', 'lifestyle']   // Only selected values
];

// In your controller
/**
 * @param list<string> $hobbies
 * @param list<string> $categories
 */
public function updatePreferences(
    #[Input] array $hobbies,      // Simple string array
    #[Input] array $categories    // Simple string array
) {
    // Direct array of strings, no object conversion needed
}
```

**Note**: For non-array parameters, use flat naming without brackets:
```html
<!-- Single object properties -->
<input name="customerName" value="Jingu">
<input name="customerEmail" value="jingu@example.com">
```

#### ArrayObject Inheritance Support

Custom ArrayObject subclasses are also supported:

```php
final class UserCollection extends ArrayObject
{
    public function getFirst(): ?UserInput
    {
        return $this[0] ?? null;
    }
}

/** 
 * @param array<UserInput> $users 
 */
public function handleUsers(
    #[Input(item: UserInput::class)] UserCollection $users
) {
    $firstUser = $users->getFirst(); // Custom method available
}
```

### Mixed with Dependency Injection

Parameters without the `#[Input]` attribute are resolved via dependency injection:

```php
use Ray\Di\Di\Named;

interface AddressServiceInterface
{
    public function findByZip(string $zip): Address;
}


interface TicketFactoryInterface
{
    public function create(string $eventId, string $ticketId): Ticket;
}

final class EventBookingInput
{
    public function __construct(
        #[Input] public readonly string $ticketId,        // From query - raw ID
        #[Input] public readonly string $email,           // From query
        #[Input] public readonly string $zip,             // From query
        #[Named('event_id')] private string $eventId,     // From DI
        private TicketFactoryInterface $ticketFactory,    // From DI
        private AddressServiceInterface $addressService,  // From DI
    ) {
        // Create complete Ticket object from ID (includes validation, expiry, etc.)
        $this->ticket = $this->ticketFactory->create($eventId, $ticketId);
        // Fully validated immutable ticket object created!
        
        if (!$this->ticket->isValid) {
            throw new InvalidTicketException(
                "Ticket {$ticketId} is invalid: {$this->ticket->getInvalidReason()}"
            );
        }
        
        // Get address from zip
        $this->address = $this->addressService->findByZip($zip);
    }
    
    public readonly Ticket $ticket;    // Complete ticket object with ID, status, etc.
    public readonly Address $address;  // Structured address object
}

// DI configuration
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(TicketFactoryInterface::class)->to(TicketFactory::class);   // Can swap with mock in tests
        $this->bind(AddressServiceInterface::class)->to(AddressService::class);
        $this->bind()->annotatedWith('event_id')->toInstance('ray-event-2025');
    }
});

$inputQuery = new InputQuery($injector);

// Usage - Factory automatically creates complete objects from IDs
try {
    $booking = $inputQuery->newInstance(EventBookingInput::class, [
        'ticketId' => 'TKT-2024-001',
        'email' => 'user@example.com',
        'zip' => '100-0001'
    ]);
    
    // $booking->ticket is a Ticket object with ID and validation status
    echo "Ticket ID: " . $booking->ticket->id; // Only valid ticket ID
    
} catch (InvalidTicketException $e) {
    // Handle expired or invalid tickets
    echo "Booking failed: " . $e->getMessage();
}
```

### Key Normalization

All query keys are normalized to camelCase:

- `user_name` → `userName`
- `user-name` → `userName`
- `UserName` → `userName`

## File Upload Integration

Ray.InputQuery provides comprehensive file upload support through integration with [Koriym.FileUpload](https://github.com/koriym/Koriym.FileUpload):

```bash
composer require koriym/file-upload
```

### Using #[InputFile] Attribute

For file uploads, use the dedicated `#[InputFile]` attribute which provides validation options:

```php
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;
use Ray\InputQuery\Attribute\InputFile;

final class UserProfileInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[InputFile(
            maxSize: 5 * 1024 * 1024,  // 5MB
            allowedTypes: ['image/jpeg', 'image/png'],
            allowedExtensions: ['jpg', 'jpeg', 'png']
        )] 
        public readonly FileUpload|ErrorFileUpload $avatar,
        #[InputFile] public readonly FileUpload|ErrorFileUpload|null $banner = null,
    ) {}
}
```

// Method usage example - Direct attribute approach

### Test-Friendly Design

File upload handling is designed to be test-friendly:

- **Production** - FileUpload library handles file uploads automatically
- **Testing** - Direct FileUpload object injection for easy mocking

```php
// Production usage - FileUpload library handles file uploads automatically
$input = $inputQuery->newInstance(UserProfileInput::class, $_POST);
// FileUpload objects are created automatically from uploaded files

// Testing usage - inject mock FileUpload objects directly for easy testing
$mockAvatar = FileUpload::create([
    'name' => 'test.jpg',
    'type' => 'image/jpeg', 
    'size' => 1024,
    'tmp_name' => '/tmp/test',
    'error' => UPLOAD_ERR_OK,
]);

$input = $inputQuery->newInstance(UserProfileInput::class, [
    'name' => 'Test User',
    'email' => 'test@example.com', 
    'avatar' => $mockAvatar,
    'banner' => null
]);
```

### Multiple File Uploads

Support for multiple file uploads using array types with validation:

```php
final class GalleryInput
{
    /**
     * @param list<FileUpload|ErrorFileUpload> $images
     */
    public function __construct(
        #[Input] public readonly string $title,
        #[InputFile(
            maxSize: 10 * 1024 * 1024,  // 10MB per file
            allowedTypes: ['image/*']
        )] 
        public readonly array $images,
    ) {}
}

// Method usage example
class GalleryController
{
    public function createGallery(GalleryInput $input): void
    {
        $savedImages = [];
        foreach ($input->images as $image) {
            if ($image instanceof FileUpload) {
                $savedImages[] = $this->saveFile($image, 'gallery/');
            } elseif ($image instanceof ErrorFileUpload) {
                // Log error but continue with other images
                $this->logger->warning('Image upload failed: ' . $image->message);
            }
        }
        
        $this->galleryService->create($input->title, $savedImages);
    }
}

// Production usage - FileUpload library handles multiple files automatically
$input = $inputQuery->newInstance(GalleryInput::class, $_POST);
// Array of FileUpload objects created automatically from uploaded files

// Testing usage - inject array of mock FileUpload objects for easy testing
$mockImages = [
    FileUpload::create(['name' => 'image1.jpg', ...]),
    FileUpload::create(['name' => 'image2.png', ...])
];

$input = $inputQuery->newInstance(GalleryInput::class, [
    'title' => 'My Gallery',
    'images' => $mockImages
]);
```
