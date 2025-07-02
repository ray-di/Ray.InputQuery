# Ray.InputQuery

Type-safe input object builder from query data.

## Installation

```bash
composer require ray/input-query
```

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

// Create object directly
$user = $inputQuery->create(UserInput::class, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

echo $user->name;  // John Doe
echo $user->email; // john@example.com

// Get method arguments
$method = new ReflectionMethod(UserController::class, 'register');
$args = $inputQuery->getArguments($method, $_POST);
// Now you can call: $controller->register(...$args)
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

## Integration

Ray.InputQuery is designed as a foundation library to be used by:

- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery) - For database query integration
- [BEAR.Resource](https://github.com/bearsunday/BEAR.Resource) - For REST resource integration

## Requirements

- PHP 8.1+
- ray/di ^2.0

## License

MIT
