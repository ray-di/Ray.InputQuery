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

$args = $inputQuery->getArguments($method, $data);
// $args[0] will be an array of UserInput objects
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

## Integration

Ray.InputQuery is designed as a foundation library to be used by:

- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery) - For database query integration
- [BEAR.Resource](https://github.com/bearsunday/BEAR.Resource) - For REST resource integration

## Requirements

- PHP 8.1+
- ray/di ^2.0

## License

MIT
