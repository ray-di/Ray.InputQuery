# Ray.InputQuery

Structured input objects from HTTP.

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

// Manual validation and object creation
$assignee = new UserInput($assigneeId, $assigneeName, $assigneeEmail);
$todo = new TodoInput($title, $assignee);

// Missing DI integration for services
$logger = $container->get(LoggerInterface::class);
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

## Documentation

Comprehensive documentation including design philosophy, AI prompts for development assistance, and sample data examples can be found in the [docs/](docs/) directory.

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
$user = $inputQuery->create(UserInput::class, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

echo $user->name;  // John Doe
echo $user->email; // john@example.com

// Method argument resolution from $_POST
$method = new ReflectionMethod(UserController::class, 'register');
$args = $inputQuery->getArguments($method, $_POST);
$controller->register(...$args);

// Or with PSR-7 Request
$args = $inputQuery->getArguments($method, $request->getParsedBody());
$controller->register(...$args);
```

### Nested Objects

Ray.InputQuery automatically creates nested objects from flat query data:

```php
final class TodoInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly UserInput $assignee  // Nested input
    ) {}
}

$todo = $inputQuery->create(TodoInput::class, [
    'title' => 'Buy milk',
    'assigneeId' => '123',
    'assigneeName' => 'John',
    'assigneeEmail' => 'john@example.com'
]);

echo $todo->title;           // Buy milk
echo $todo->assignee->name;  // John
```

### Mixed with Dependency Injection

Parameters without the `#[Input]` attribute are resolved via dependency injection:

```php
use Ray\Di\Di\Named;

final class OrderInput
{
    public function __construct(
        #[Input] public readonly string $orderId,         // From query
        #[Input] public readonly CustomerInput $customer,  // From query
        #[Named('tax.rate')] private float $taxRate,      // From DI
        private LoggerInterface $logger                    // From DI
    ) {}
}
```

### Key Normalization

All query keys are normalized to camelCase:

- `user_name` → `userName`
- `user-name` → `userName`
- `UserName` → `userName`

### Input Format Requirements

Ray.InputQuery expects **flat key-value pairs** as input. Nested array structures are not supported:

```php
// ✅ Correct - Flat structure
$data = [
    'customerName' => 'John Doe',
    'customerEmail' => 'john@example.com',
    'shippingCity' => 'Tokyo'
];

// ❌ Wrong - Nested arrays (e.g., from customer[name] form fields)
$data = [
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
];
```

**Why this restriction?** When nested objects are flattened for database operations, all property names must be globally unique to avoid conflicts. This design ensures predictable parameter binding and prevents naming collisions.
For HTML forms, use flat naming:

```html
<!-- ✅ Correct -->
<input name="customerName">
<input name="customerEmail">

<!-- ❌ Avoid -->
<input name="customer[name]">
<input name="customer[email]">
```

## Integration

Ray.InputQuery is designed as a foundation library to be used by:

- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery) - For database query integration
- [BEAR.Resource](https://github.com/bearsunday/BEAR.Resource) - For REST resource integration

## Requirements

- PHP 8.1+
- ray/di ^2.0

## License

MIT
