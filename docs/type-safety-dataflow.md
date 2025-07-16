# Type Safety and Data Flow in Ray.InputQuery

## Introduction

Ray.InputQuery implements a type-safe data transformation pipeline that maintains type information throughout the entire request-response cycle. This document explores the technical details of how type safety is achieved and preserved across different layers.

## The Type-Safe Pipeline

### Stage 1: HTTP → Structured Input Objects

```php
// Raw HTTP data (untyped)
$_POST = [
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'order_items' => [
        ['product_id' => '123', 'quantity' => '2'],
        ['product_id' => '456', 'quantity' => '1']
    ]
];

// Transformation to typed objects
class OrderInput
{
    public function __construct(
        #[Input] public readonly string $customerName,
        #[Input] public readonly string $customerEmail,
        #[Input(item: OrderItemInput::class)] public readonly array $orderItems,
    ) {}
}

class OrderItemInput
{
    public function __construct(
        #[Input] public readonly string $productId,
        #[Input] public readonly int $quantity,
    ) {}
}

// Type-safe instantiation
$orderInput = $inputQuery->newInstance(OrderInput::class, $_POST);
// Result: OrderInput with guaranteed types
```

### Stage 2: Input Objects → SQL Parameters

```php
// Flattening nested structures for SQL
$toArray = new ToArray();
$sqlParams = $toArray($orderInput);

// Result: Flat associative array
// [
//     'customerName' => 'John Doe',
//     'customerEmail' => 'john@example.com',
//     'orderItems' => [
//         ['productId' => '123', 'quantity' => 2],
//         ['productId' => '456', 'quantity' => 1]
//     ]
// ]
```

### Stage 3: SQL Parameters → Database Query

```php
// Type-safe SQL parameter binding
$sql = "
    INSERT INTO orders (customer_name, customer_email) 
    VALUES (:customerName, :customerEmail)
";

$stmt = $pdo->prepare($sql);
$stmt->execute($sqlParams); // Type information preserved
```

### Stage 4: Database Results → Domain Objects

```php
// Raw database result
$orderRow = [
    'id' => '789',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'created_at' => '2023-12-01 10:30:00'
];

// Transformation to typed domain object
class Order
{
    public function __construct(
        public readonly OrderId $id,
        public readonly Customer $customer,
        public readonly DateTime $createdAt,
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            new OrderId($data['id']),
            new Customer($data['customer_name'], $data['customer_email']),
            new DateTime($data['created_at'])
        );
    }
}
```

### Stage 5: Domain Objects → View Data

```php
// Structured view data
$viewData = [
    'order' => $order,
    'items' => $orderItems,
    'total' => $order->calculateTotal(),
];

// Template rendering with type information
echo $twig->render('order.html.twig', $viewData);
```

## Type Safety Mechanisms

### 1. Constructor-Based Validation

```php
class ProductInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly Money $price,
        #[Input] public readonly Category $category,
    ) {
        // Validation happens at construction time
        if (strlen($this->name) < 2) {
            throw new ValidationException('Product name too short');
        }
        
        if ($this->price->isNegative()) {
            throw new ValidationException('Price cannot be negative');
        }
    }
}
```

### 2. Type Coercion and Conversion

```php
// InputQuery handles type conversion automatically
class UserInput
{
    public function __construct(
        #[Input] public readonly int $age,        // '25' → 25
        #[Input] public readonly bool $isActive,  // 'true' → true
        #[Input] public readonly DateTime $birthDate, // '1990-01-01' → DateTime
    ) {}
}

// Custom type conversion
private function convertScalar(mixed $value, ReflectionNamedType $type): mixed
{
    return match ($type->getName()) {
        'string' => is_string($value) ? $value : (string) $value,
        'int' => is_int($value) ? $value : (int) $value,
        'float' => is_float($value) ? $value : (float) $value,
        'bool' => is_bool($value) ? $value : (bool) $value,
        default => $value
    };
}
```

### 3. Reflection-Based Type Introspection

```php
// InputQuery uses reflection to understand types
private function resolveInputParameter(ReflectionParameter $param, array $query): mixed
{
    $type = $param->getType();
    
    if ($type instanceof ReflectionNamedType) {
        if ($type->isBuiltin()) {
            return $this->resolveBuiltinType($param, $query, $type);
        }
        
        // Handle custom objects recursively
        return $this->resolveObjectType($param, $query, $type);
    }
    
    // Handle union types
    if ($type instanceof ReflectionUnionType) {
        return $this->resolveUnionType($param, $query, $type);
    }
}
```

## Advanced Type Safety Features

### 1. Generic Array Handling

```php
class SearchInput
{
    public function __construct(
        #[Input] public readonly string $query,
        #[Input(item: FilterInput::class)] public readonly array $filters,
    ) {}
}

// Ensures each array item is of type FilterInput
private function createArrayOfInputs(string $paramName, array $query, string $itemClass): array
{
    $result = [];
    foreach ($query[$paramName] as $key => $itemData) {
        $result[$key] = $this->newInstance($itemClass, $itemData);
    }
    return $result;
}
```

### 2. Nullable Type Support

```php
class OptionalInput
{
    public function __construct(
        #[Input] public readonly string $required,
        #[Input] public readonly ?string $optional = null,
        #[Input] public readonly ?CustomerInput $customer = null,
    ) {}
}

// Handles nullable types gracefully
if ($param->allowsNull() && $value === null) {
    return null;
}
```

### 3. Union Type Resolution

```php
class FlexibleInput
{
    public function __construct(
        #[Input] public readonly string|int $identifier,
        #[Input] public readonly FileUpload|ErrorFileUpload $upload,
    ) {}
}

// Resolves union types based on context
private function resolveUnionType(ReflectionParameter $param, array $query, ReflectionUnionType $type): mixed
{
    foreach ($type->getTypes() as $unionType) {
        try {
            return $this->tryResolveType($param, $query, $unionType);
        } catch (Exception $e) {
            // Try next type in union
            continue;
        }
    }
    
    throw new TypeResolutionException("Cannot resolve union type");
}
```

## Error Handling and Type Safety

### 1. Early Validation

```php
try {
    $userInput = $inputQuery->newInstance(UserInput::class, $_POST);
} catch (ValidationException $e) {
    // Handle validation errors at the boundary
    return new JsonResponse([
        'error' => 'Validation failed',
        'details' => $e->getErrors()
    ], 400);
}
```

### 2. Type-Specific Error Messages

```php
class ValidationException extends Exception
{
    public function __construct(
        public readonly string $field,
        public readonly string $expectedType,
        public readonly mixed $actualValue,
    ) {
        parent::__construct(
            "Field '{$field}' expected {$expectedType}, got " . gettype($actualValue)
        );
    }
}
```

### 3. Cascading Validation

```php
class AddressInput
{
    public function __construct(
        #[Input] public readonly string $street,
        #[Input] public readonly string $city,
        #[Input] public readonly CountryCode $country,
    ) {
        // Nested validation
        if ($this->country->requiresPostalCode() && empty($this->postalCode)) {
            throw new ValidationException("Postal code required for {$this->country}");
        }
    }
}
```

## Performance Considerations

### 1. Reflection Caching

```php
class InputQuery
{
    private static array $reflectionCache = [];
    
    private function getReflectionClass(string $className): ReflectionClass
    {
        if (!isset(self::$reflectionCache[$className])) {
            self::$reflectionCache[$className] = new ReflectionClass($className);
        }
        
        return self::$reflectionCache[$className];
    }
}
```

### 2. Lazy Loading

```php
class LazyInput
{
    private ?ExpensiveObject $expensive = null;
    
    public function getExpensive(): ExpensiveObject
    {
        if ($this->expensive === null) {
            $this->expensive = $this->createExpensiveObject();
        }
        
        return $this->expensive;
    }
}
```

### 3. Minimal Object Creation

```php
// Only create objects when needed
if ($this->hasNestedData($paramName, $query)) {
    return $this->newInstance($className, $this->extractNestedQuery($paramName, $query));
}

return $this->getDefaultValue($param);
```

## Integration with Static Analysis

### 1. PHPStan Integration

```php
/**
 * @template T of object
 * @param class-string<T> $class
 * @return T
 */
public function newInstance(string $class, array $query): object
{
    // PHPStan understands the return type
    return $reflection->newInstanceArgs($args);
}
```

### 2. Psalm Annotations

```php
/**
 * @psalm-type Query = array<string, mixed>
 * @psalm-type ParameterValue = scalar|array<array-key, mixed>|object|null
 */
final class InputQuery implements InputQueryInterface
{
    /** @param Query $query */
    private function resolveParameter(ReflectionParameter $param, array $query): mixed
    {
        // Psalm tracks types through the pipeline
    }
}
```

### 3. IDE Support

```php
// IDE autocompletion works because types are preserved
$userInput = $inputQuery->newInstance(UserInput::class, $_POST);
$userInput->name; // IDE knows this is string
$userInput->email; // IDE knows this is string
$userInput->age; // IDE knows this is int
```

## Best Practices for Type Safety

### 1. Use Value Objects

```php
// Instead of primitive types
class UserInput
{
    public function __construct(
        #[Input] public readonly Email $email,    // Value object
        #[Input] public readonly Age $age,        // Value object
        #[Input] public readonly Username $name,  // Value object
    ) {}
}
```

### 2. Leverage Enums

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class UserInput
{
    public function __construct(
        #[Input] public readonly Status $status,
    ) {}
}
```

### 3. Composition over Inheritance

```php
// Compose complex inputs from simpler ones
class OrderInput
{
    public function __construct(
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ShippingInput $shipping,
        #[Input] public readonly PaymentInput $payment,
    ) {}
}
```

## Testing Type Safety

### 1. Type Contract Tests

```php
class TypeSafetyTest extends TestCase
{
    public function testStringToIntConversion(): void
    {
        $input = $this->inputQuery->newInstance(AgeInput::class, ['age' => '25']);
        
        $this->assertIsInt($input->age);
        $this->assertSame(25, $input->age);
    }
    
    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->inputQuery->newInstance(AgeInput::class, ['age' => 'invalid']);
    }
}
```

### 2. Integration Tests

```php
class DataFlowTest extends TestCase
{
    public function testCompleteTypePreservation(): void
    {
        // HTTP → Input
        $input = $this->inputQuery->newInstance(OrderInput::class, $httpData);
        $this->assertInstanceOf(OrderInput::class, $input);
        
        // Input → SQL
        $params = $this->toArray->__invoke($input);
        $this->assertIsArray($params);
        
        // SQL → Domain
        $order = Order::fromArray($dbResult);
        $this->assertInstanceOf(Order::class, $order);
    }
}
```

## Conclusion

Ray.InputQuery's type safety system provides:

1. **Compile-time Type Checking** through static analysis
2. **Runtime Type Validation** through constructor validation
3. **Automatic Type Conversion** for common transformations
4. **Error Boundary Management** with clear failure points
5. **IDE Integration** for better developer experience

This comprehensive type safety ensures that data integrity is maintained throughout the entire application lifecycle, reducing bugs and improving maintainability.