# Claude.md - Implementation Guide for Ray.InputQuery

## Project Overview

Ray.InputQuery is a foundation library that creates type-safe PHP objects from flat query data. It must be simple, have minimal dependencies, and be designed to be used by other libraries rather than directly by end users.

## CRITICAL DESIGN DECISION

**The #[Input] attribute is applied to PARAMETERS, not classes.**

```php
// CORRECT - #[Input] on parameters
final class UserInput {
    public function __construct(
        #[Input] public readonly string $name,    // from query
        #[Input] public readonly string $email,   // from query
        private LoggerInterface $logger           // from DI
    ) {}
}

// WRONG - Do not put #[Input] on class
#[Input]  // ❌ WRONG!
final class UserInput { ... }
```

This design allows clear distinction between:
- Parameters that come from query data (#[Input])
- Parameters that come from dependency injection (no #[Input])

## Core Design Principles

1. **Minimal Dependencies**: Only depend on `ray/di` for `InjectorInterface`
2. **Single Responsibility**: Convert query data to input objects, nothing more
3. **No Framework Lock-in**: Must work independently of any specific framework
4. **Type Safety**: Leverage PHP's type system fully
5. **Zero Configuration**: Should work out of the box with just attributes

## Implementation Requirements

### Directory Structure

```
src/
├── Attribute/
│   └── Input.php           # The #[Input] attribute for parameters
├── InputQuery.php          # Main implementation
├── InputQueryInterface.php # Core interface
└── Exception/              # Add exceptions as needed
    └── InputQueryException.php

tests/
├── InputQueryTest.php
├── Fake/                   # Test fixtures
│   ├── UserInput.php       # Example with all #[Input]
│   ├── TodoInput.php       # Example with nested #[Input]
│   ├── MixedInput.php      # Example with #[Input] and DI
│   └── TestService.php     # Service for DI tests
└── bootstrap.php
```

### Core Components

#### 1. Input Attribute

```php
namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
}
```

#### 2. InputQueryInterface

```php
namespace Ray\InputQuery;

interface InputQueryInterface
{
    /**
     * Get method arguments from query data
     * 
     * @param \ReflectionMethod $method
     * @param array<string, mixed> $query
     * @return array<int, mixed>
     */
    public function getArguments(\ReflectionMethod $method, array $query): array;
    
    /**
     * Create object from query data
     * 
     * @param class-string $class
     * @param array<string, mixed> $query
     */
    public function create(string $class, array $query): object;
}
```

#### 3. InputQuery Implementation

Key implementation details:

1. **Parameter Analysis**
   - Check each constructor parameter for `#[Input]` attribute
   - Parameters with `#[Input]` come from query
   - Parameters without `#[Input]` come from DI
   - Apply this rule consistently for both scalars and objects

2. **Query Normalization**
   - Convert all keys to camelCase
   - Handle snake_case, kebab-case, PascalCase
   - Example: `user_name`, `user-name`, `UserName` → `userName`

3. **Nested Object Resolution**
   - For parameters with `#[Input]` attribute and object type
   - Extract prefixed keys from flat query
   - Recursively create nested objects
   - Example: `authorName`, `authorEmail` → `AuthorInput { name, email }`

4. **Dependency Injection**
   - For parameters without `#[Input]`, use injector
   - Support #[Named] for scalar DI values
   - Example: `#[Named('app.env')] private string $environment`

### Algorithm for Parameter Resolution

```
For each constructor parameter:
1. Has #[Input] attribute?
   - YES: Get value from query (apply nesting logic if object type)
   - NO: Get value from DI container

Example:
final class OrderInput {
    public function __construct(
        #[Input] public readonly string $orderId,      // from query
        #[Input] public readonly CustomerInput $customer, // from query (nested)
        private LoggerInterface $logger                 // from DI
    ) {}
}
```

### Error Handling

- Parameters with #[Input] but missing from query: Use parameter default or null if allowed
- Parameters without #[Input] that can't be resolved by DI: Use parameter default or throw exception
- Type conversion failures: Let PHP handle naturally or provide clear error messages
- Circular dependencies in nested inputs: Implement depth limit

### Testing Strategy

1. **Unit Tests**
   ```php
   public function testCreateObject(): void
   {
       // Test object creation with #[Input] attributes
       $query = ['name' => 'John', 'email' => 'john@example.com'];
       $user = $inputQuery->create(UserInput::class, $query);
       assertSame('John', $user->name);
   }
   
   public function testGetArguments(): void
   {
       // Test method arguments generation
       $method = new ReflectionMethod(TodoController::class, 'create');
       $query = ['title' => 'Task', 'assigneeId' => '123'];
       $args = $inputQuery->getArguments($method, $query);
       assertInstanceOf(TodoInput::class, $args[0]);
   }
   
   public function testNestedInput(): void
   {
       // Test nested object creation
       $query = [
           'title' => 'Task',
           'assigneeId' => '123',
           'assigneeName' => 'John'
       ];
       $task = $inputQuery->create(TaskInput::class, $query);
       assertInstanceOf(UserInput::class, $task->assignee);
   }
   
   public function testMixedInputAndDI(): void
   {
       // Test combination of #[Input] parameters and DI parameters
       $method = new ReflectionMethod(OrderService::class, 'process');
       $args = $inputQuery->getArguments($method, ['orderId' => '123']);
       assertSame('123', $args[0]->orderId);
       assertInstanceOf(LoggerInterface::class, $args[0]->getLogger());
   }
   ```

2. **Edge Cases**
   - Empty query with all #[Input] parameters having defaults
   - Null values for nullable #[Input] parameters
   - Deep nesting (3+ levels) with #[Input]
   - Array properties with #[Input]
   - Optional parameters without #[Input] (from DI)
   - Scalar parameters with #[Named] (from DI)
   - Mix of #[Input] and non-#[Input] parameters

### Performance Considerations

1. **Reflection Caching**
   - Consider caching reflection results
   - Balance between memory and CPU usage

2. **Recursion Limits**
   - Implement reasonable depth limit (e.g., 10 levels)
   - Prevent infinite recursion

### Integration Points

Libraries using Ray.InputQuery will:

1. **Ray.MediaQuery**
   - Check method parameters for #[Input] attributes
   - Use InputQuery to create objects from query data
   - Flatten Input objects for SQL binding

2. **BEAR.Resource**
   - Detect parameters with #[Input] attribute
   - Pass query data to InputQuery for those parameters
   - Other parameters resolved through standard DI

Example integration code:
```php
// In Ray.MediaQuery
class EnhancedParamInjector implements ParamInjectorInterface
{
    public function getArguments(MethodInvocation $invocation): array
    {
        $method = $invocation->getMethod();
        $query = $this->extractQueryFromInvocation($invocation);
        
        // Use InputQuery to generate all arguments
        return $this->inputQuery->getArguments($method, $query);
    }
}

// In BEAR.Resource
class ResourceInvoker
{
    public function invoke($resource, string $method, array $query): ResourceObject
    {
        $reflectionMethod = new ReflectionMethod($resource, $method);
        $args = $this->inputQuery->getArguments($reflectionMethod, $query);
        
        return $resource->$method(...$args);
    }
}
```

### Future Considerations (Do Not Implement Now)

- JSON Schema validation (validate query data before object creation)
- Custom type converters for complex types
- Attribute-based validation rules
- Error message localization
- Caching of reflection data
- Support for `#[Input(from: 'header')]` or `#[Input(from: 'cookie')]`

## Code Quality Standards

1. **PSR-12** coding style
2. **PHPStan** level 8
3. **100%** test coverage for public API
4. **PHPDoc** for all public methods
5. **Semantic versioning**
6. **Clear #[Input] usage** - every parameter from query must have #[Input]

## Example Implementation Pattern

```php
final class InputQuery implements InputQueryInterface
{
    public function __construct(
        private InjectorInterface $injector
    ) {}
    
    public function getArguments(ReflectionMethod $method, array $query): array
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            $args[] = $this->resolveParameter($param, $query);
        }
        return $args;
    }
    
    public function create(string $class, array $query): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new $class();
        }
        
        $args = $this->getArguments($constructor, $query);
        return $reflection->newInstanceArgs($args);
    }
    
    private function resolveParameter(
        ReflectionParameter $param,
        array $query
    ): mixed {
        $hasInputAttribute = !empty($param->getAttributes(Input::class));
        
        if (!$hasInputAttribute) {
            // No #[Input] attribute - get from DI
            return $this->resolveFromDI($param);
        }
        
        // Has #[Input] attribute - get from query
        $type = $param->getType();
        $paramName = $param->getName();
        
        if (!$type instanceof ReflectionNamedType) {
            return $query[$paramName] ?? null;
        }
        
        if ($type->isBuiltin()) {
            // Scalar type with #[Input]
            return $this->convertScalar($query[$paramName] ?? null, $type);
        }
        
        // Object type with #[Input] - create nested
        return $this->create($type->getName(), $this->extractNestedQuery($paramName, $query));
    }
}
```

## Development Workflow

1. Start with failing tests
2. Implement minimal code to pass
3. Refactor for clarity
4. Add edge case tests
5. Document public API

**Remember the key rule**: #[Input] attribute on parameters determines data source:
- With #[Input] → from query
- Without #[Input] → from DI

Keep it simple, focused, and reliable.
