# Input-First Architecture

## Overview

Input-First Architecture is a modern approach to web application design that prioritizes structured input validation and type safety throughout the entire data flow pipeline. This architectural pattern ensures type safety and consistency from HTTP requests to database queries to HTML responses.

## Data Flow Pipeline

```
HTTP Request → PHP Domain Objects → SQL Parameters → PHP Domain Objects → View Templates → HTML Response
```

### Complete Flow Example

```php
// 1. HTTP → PHP Domain
$orderInput = $inputQuery->newInstance(OrderInput::class, $_POST);

// 2. PHP Domain → SQL 
$toArray = new ToArray();
$sqlParams = $toArray($orderInput);

// 3. SQL Query
$orders = $repository->findByParams($sqlParams);

// 4. SQL → PHP Domain
$orderList = array_map(fn($row) => Order::fromArray($row), $orders);

// 5. PHP Domain → View
$viewData = ['orders' => $orderList];

// 6. View → HTML
echo $twig->render('orders.html.twig', $viewData);
```

## Core Principles

### 1. Type Safety First
Every transformation maintains type information:
- Input validation with structured objects
- Domain objects with proper typing
- View data with known structure

### 2. Immutable Transformations
Each stage produces new data without side effects:
- HTTP data → Input objects (immutable)
- Input objects → SQL parameters (pure function)
- SQL results → Domain objects (factory pattern)

### 3. Single Responsibility
Each layer has a specific purpose:
- **Input Layer**: Validation and structure
- **Domain Layer**: Business logic
- **Persistence Layer**: Data storage
- **View Layer**: Presentation

## Benefits

### Type Safety
```php
// Traditional approach - no type safety
$name = $_POST['customer_name'] ?? ''; // string|null
$age = (int)$_POST['customer_age']; // could be 0

// Input-First approach - type guaranteed
class CustomerInput {
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly int $age,
    ) {}
}
```

### Predictable Data Flow
```php
// Each transformation is explicit and typed
HTTP Array → CustomerInput → SQL Params → Customer Entity → View Data
array       CustomerInput   array       Customer        array
```

### Better Error Handling
```php
try {
    $input = $inputQuery->newInstance(CustomerInput::class, $_POST);
} catch (ValidationException $e) {
    // Handle validation errors at the boundary
    return new JsonResponse(['errors' => $e->getErrors()], 400);
}
```

## Comparison with Traditional Approaches

### Traditional MVC
```php
// Controller directly handles arrays
public function createUser(Request $request): Response
{
    $name = $request->get('name'); // mixed
    $email = $request->get('email'); // mixed
    
    // Validation scattered throughout
    if (empty($name)) throw new Exception('Name required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email');
    
    // Database call with raw data
    $user = $this->userRepository->create(['name' => $name, 'email' => $email]);
    
    return $this->render('user.html.twig', ['user' => $user]);
}
```

### Input-First Approach
```php
// Controller works with typed objects
public function createUser(Request $request): Response
{
    // Validation happens at the boundary
    $userInput = $this->inputQuery->newInstance(UserInput::class, $request->request->all());
    
    // Business logic with typed objects
    $user = $this->userService->createUser($userInput);
    
    // View with structured data
    return $this->render('user.html.twig', ['user' => $user]);
}
```

## Key Components

### Input Objects
```php
class OrderInput
{
    public function __construct(
        #[Input] public readonly string $customerName,
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly array $items,
    ) {}
}
```

### ToArray Transformer
```php
$toArray = new ToArray();
$sqlParams = $toArray($orderInput);
// Flattens nested objects for SQL parameter binding
```

### Domain Objects
```php
class Order
{
    public function __construct(
        public readonly string $id,
        public readonly Customer $customer,
        public readonly array $items,
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            Customer::fromArray($data),
            $data['items']
        );
    }
}
```

## Integration Points

### With Dependency Injection
```php
// Input objects can receive services
class OrderInput
{
    public function __construct(
        #[Input] public readonly string $customerId,
        private CustomerRepository $customerRepository,
    ) {
        // Validation can use injected services
        if (!$this->customerRepository->exists($this->customerId)) {
            throw new ValidationException('Customer not found');
        }
    }
}
```

### With Form Libraries
```php
// Symfony Form integration
$form = $this->createForm(OrderType::class);
$form->handleRequest($request);

if ($form->isValid()) {
    $orderInput = $this->inputQuery->newInstance(
        OrderInput::class, 
        $form->getData()
    );
}
```

### With API Documentation
```php
// OpenAPI integration through reflection
class OrderInput
{
    public function __construct(
        /** Customer's full name */
        #[Input]
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $customerName,
    ) {}
}
```

## Best Practices

### 1. Keep Input Objects Simple
```php
// Good - focused on structure
class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
    ) {}
}

// Avoid - business logic in input
class UserInput
{
    public function save(): User { /* ... */ } // Don't do this
}
```

### 2. Use Value Objects
```php
class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly Email $email, // Value object
    ) {}
}
```

### 3. Leverage Type System
```php
class SearchInput
{
    public function __construct(
        #[Input] public readonly string $query,
        #[Input] public readonly int $page = 1,
        #[Input] public readonly int $limit = 20,
        #[Input] public readonly SortDirection $sort = SortDirection::ASC,
    ) {}
}
```

## Advanced Patterns

### Nested Input Validation
```php
class OrderInput
{
    public function __construct(
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly AddressInput $shipping,
        #[Input] public readonly AddressInput $billing,
    ) {}
}
```

### Array Input Processing
```php
class BulkOrderInput
{
    public function __construct(
        #[Input(item: OrderInput::class)]
        public readonly array $orders,
    ) {}
}
```

### Conditional Validation
```php
class PaymentInput
{
    public function __construct(
        #[Input] public readonly PaymentMethod $method,
        #[Input] public readonly ?CreditCardInput $creditCard = null,
        #[Input] public readonly ?BankTransferInput $bankTransfer = null,
    ) {
        if ($this->method === PaymentMethod::CREDIT_CARD && $this->creditCard === null) {
            throw new ValidationException('Credit card details required');
        }
    }
}
```

## Testing Strategies

### Unit Testing Input Objects
```php
class OrderInputTest extends TestCase
{
    public function testValidOrder(): void
    {
        $input = new OrderInput(
            customerName: 'John Doe',
            items: [['id' => 1, 'quantity' => 2]]
        );
        
        $this->assertSame('John Doe', $input->customerName);
    }
    
    public function testInvalidOrder(): void
    {
        $this->expectException(ValidationException::class);
        
        new OrderInput(
            customerName: '', // Invalid
            items: []
        );
    }
}
```

### Integration Testing Data Flow
```php
class OrderFlowTest extends TestCase
{
    public function testCompleteOrderFlow(): void
    {
        // Simulate HTTP input
        $httpData = [
            'customer_name' => 'John Doe',
            'items' => [['id' => 1, 'quantity' => 2]]
        ];
        
        // Input transformation
        $orderInput = $this->inputQuery->newInstance(OrderInput::class, $httpData);
        
        // SQL parameter generation
        $sqlParams = $this->toArray->__invoke($orderInput);
        
        // Verify complete flow
        $this->assertArrayHasKey('customer_name', $sqlParams);
        $this->assertSame('John Doe', $sqlParams['customer_name']);
    }
}
```

## Conclusion

Input-First Architecture provides a robust foundation for modern web applications by:

1. **Ensuring Type Safety** throughout the entire data pipeline
2. **Reducing Bugs** through early validation and structured data
3. **Improving Developer Experience** with IDE support and clear contracts
4. **Enabling Better Testing** with predictable data transformations
5. **Supporting Modern Patterns** like GraphQL, Event Sourcing, and CQRS

This approach represents a significant evolution from traditional string-based web development toward a more functional, type-safe programming model.