# Type-Driven Development with Ray.InputQuery

## Introduction

Type-Driven Development (TDD) is a programming methodology where types and type signatures guide the design and implementation of software. Ray.InputQuery embraces this approach by making types central to the application architecture, from HTTP input validation to database queries.

## Core Principles of Type-Driven Development

### 1. Types as Specifications

Types serve as executable specifications that describe the expected behavior and constraints of your system.

```php
// Type as specification
class CreateUserInput
{
    public function __construct(
        #[Input] 
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $name,
        
        #[Input]
        #[Assert\Email]
        public readonly string $email,
        
        #[Input]
        #[Assert\Range(min: 13, max: 120)]
        public readonly int $age,
        
        #[Input]
        public readonly UserRole $role = UserRole::USER,
    ) {}
}

// This type tells us exactly what a valid user creation request looks like
```

### 2. Type-First Design Process

#### Step 1: Define the Domain Types

```php
// Start with the domain model
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
}

class User
{
    public function __construct(
        public readonly UserId $id,
        public readonly string $name,
        public readonly Email $email,
        public readonly Age $age,
        public readonly UserRole $role,
        public readonly DateTime $createdAt,
    ) {}
}
```

#### Step 2: Define Input Types

```php
// Design input types based on domain requirements
class UpdateUserProfileInput
{
    public function __construct(
        #[Input] public readonly UserId $userId,
        #[Input] public readonly ?string $name = null,
        #[Input] public readonly ?Email $email = null,
        #[Input] public readonly ?ProfilePictureInput $profilePicture = null,
    ) {}
}

class ProfilePictureInput
{
    public function __construct(
        #[InputFile(maxSize: 5000000, allowedTypes: ['image/jpeg', 'image/png'])]
        public readonly FileUpload $file,
        #[Input] public readonly string $altText,
    ) {}
}
```

#### Step 3: Define Service Interfaces

```php
// Let types guide interface design
interface UserServiceInterface
{
    public function createUser(CreateUserInput $input): User;
    public function updateProfile(UpdateUserProfileInput $input): User;
    public function findUser(UserId $id): ?User;
    public function searchUsers(UserSearchInput $input): UserSearchResult;
}
```

#### Step 4: Implement Based on Types

```php
class UserService implements UserServiceInterface
{
    public function createUser(CreateUserInput $input): User
    {
        // Type constraints guide implementation
        $userId = UserId::generate();
        
        $user = new User(
            $userId,
            $input->name,
            $input->email,
            $input->age,
            $input->role,
            new DateTime()
        );
        
        $this->userRepository->save($user);
        
        return $user;
    }
}
```

## Value Objects and Type Safety

### 1. Primitive Obsession Prevention

```php
// Avoid primitive obsession
class BadExample
{
    public function transferMoney(string $fromAccount, string $toAccount, float $amount): void
    {
        // Easy to mix up parameters!
        $this->bankService->transfer($toAccount, $fromAccount, $amount);
    }
}

// Use value objects instead
class GoodExample
{
    public function transferMoney(AccountId $from, AccountId $to, Money $amount): void
    {
        // Impossible to mix up - compiler will catch errors
        $this->bankService->transfer($from, $to, $amount);
    }
}
```

### 2. Value Object Implementation

```php
class Email
{
    private string $value;
    
    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }
        
        $this->value = strtolower($email);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}

class Money
{
    public function __construct(
        private int $amount,      // Store in smallest currency unit
        private Currency $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }
    
    public function add(Money $other): Money
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }
        
        return new Money($this->amount + $other->amount, $this->currency);
    }
    
    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }
}
```

### 3. Integration with Ray.InputQuery

```php
class PaymentInput
{
    public function __construct(
        #[Input] public readonly Money $amount,
        #[Input] public readonly AccountId $fromAccount,
        #[Input] public readonly AccountId $toAccount,
        #[Input] public readonly string $description,
    ) {
        // Business rules in the input type
        if ($this->amount->isLessThanOrEqual(Money::zero($this->amount->getCurrency()))) {
            throw new ValidationException('Payment amount must be positive');
        }
        
        if ($this->fromAccount->equals($this->toAccount)) {
            throw new ValidationException('Cannot transfer to the same account');
        }
    }
}
```

## Advanced Type Patterns

### 1. Phantom Types

```php
// Use phantom types to track state
class UserId
{
    private function __construct(private string $value) {}
    
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }
    
    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new InvalidArgumentException('Invalid user ID format');
        }
        
        return new self($id);
    }
    
    public function toString(): string
    {
        return $this->value;
    }
}

// Different ID types prevent mixing
class ProductId
{
    private function __construct(private string $value) {}
    
    public static function fromString(string $id): self
    {
        return new self($id);
    }
}

// Compiler prevents this error:
// $userService->findUser(ProductId::fromString('123')); // Type error!
```

### 2. State Machines with Types

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

class Order
{
    public function __construct(
        public readonly OrderId $id,
        public readonly array $items,
        public readonly OrderStatus $status = OrderStatus::PENDING,
    ) {}
    
    public function confirm(): Order
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new InvalidStateException('Can only confirm pending orders');
        }
        
        return new Order($this->id, $this->items, OrderStatus::CONFIRMED);
    }
    
    public function ship(): Order
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            throw new InvalidStateException('Can only ship confirmed orders');
        }
        
        return new Order($this->id, $this->items, OrderStatus::SHIPPED);
    }
}
```

### 3. Generic Input Types

```php
// Generic pagination input
class PaginatedInput
{
    public function __construct(
        #[Input] public readonly int $page = 1,
        #[Input] public readonly int $limit = 20,
        #[Input] public readonly string $sortBy = 'id',
        #[Input] public readonly SortDirection $sortDirection = SortDirection::ASC,
    ) {
        if ($this->page < 1) {
            throw new ValidationException('Page must be >= 1');
        }
        
        if ($this->limit < 1 || $this->limit > 100) {
            throw new ValidationException('Limit must be between 1 and 100');
        }
    }
}

// Specific search inputs extend generic pagination
class UserSearchInput extends PaginatedInput
{
    public function __construct(
        #[Input] public readonly ?string $name = null,
        #[Input] public readonly ?Email $email = null,
        #[Input] public readonly ?UserRole $role = null,
        int $page = 1,
        int $limit = 20,
        string $sortBy = 'createdAt',
        SortDirection $sortDirection = SortDirection::DESC,
    ) {
        parent::__construct($page, $limit, $sortBy, $sortDirection);
    }
}
```

## Testing Type-Driven Code

### 1. Property-Based Testing

```php
class MoneyTest extends TestCase
{
    /**
     * @dataProvider validAmountProvider
     */
    public function testMoneyCreation(int $amount, Currency $currency): void
    {
        $money = new Money($amount, $currency);
        
        $this->assertSame($amount, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
    }
    
    public function validAmountProvider(): array
    {
        return [
            [0, Currency::USD],
            [100, Currency::EUR],
            [999999, Currency::JPY],
        ];
    }
    
    /**
     * @dataProvider invalidAmountProvider
     */
    public function testInvalidMoneyCreation(int $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Money($amount, Currency::USD);
    }
    
    public function invalidAmountProvider(): array
    {
        return [
            [-1],
            [-100],
            [PHP_INT_MIN],
        ];
    }
}
```

### 2. Type Contract Testing

```php
class UserServiceTest extends TestCase
{
    public function testCreateUserReturnsCorrectType(): void
    {
        $input = new CreateUserInput(
            name: 'John Doe',
            email: new Email('john@example.com'),
            age: new Age(30),
            role: UserRole::USER
        );
        
        $user = $this->userService->createUser($input);
        
        // Test type contracts
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(UserId::class, $user->id);
        $this->assertInstanceOf(Email::class, $user->email);
        $this->assertSame($input->name, $user->name);
    }
    
    public function testCreateUserWithInvalidInputThrows(): void
    {
        $this->expectException(ValidationException::class);
        
        $input = new CreateUserInput(
            name: '', // Invalid
            email: new Email('john@example.com'),
            age: new Age(30)
        );
        
        $this->userService->createUser($input);
    }
}
```

### 3. Integration Testing with Types

```php
class UserControllerIntegrationTest extends TestCase
{
    public function testCompleteUserCreationFlow(): void
    {
        // Test HTTP → Input → Domain → Database → Response
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'role' => 'user'
        ]);
        
        $response->assertStatus(201);
        
        $userData = $response->json();
        $this->assertArrayHasKey('id', $userData);
        $this->assertSame('John Doe', $userData['name']);
        
        // Verify database state
        $user = $this->userRepository->find(UserId::fromString($userData['id']));
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('john@example.com', $user->email->getValue());
    }
}
```

## Type-Driven API Documentation

### 1. Self-Documenting Types

```php
/**
 * Represents a request to create a new blog post
 */
class CreatePostInput
{
    public function __construct(
        /** The title of the blog post (2-200 characters) */
        #[Input]
        #[Assert\Length(min: 2, max: 200)]
        public readonly string $title,
        
        /** The main content of the post (markdown supported) */
        #[Input]
        #[Assert\NotBlank]
        public readonly string $content,
        
        /** Tags for categorizing the post */
        #[Input(item: 'string')]
        #[Assert\Count(max: 10)]
        public readonly array $tags = [],
        
        /** Whether the post should be published immediately */
        #[Input]
        public readonly bool $published = false,
        
        /** Scheduled publication date (optional) */
        #[Input]
        public readonly ?DateTime $publishAt = null,
    ) {
        if ($this->published && $this->publishAt !== null) {
            throw new ValidationException('Cannot set publishAt when published is true');
        }
        
        if (!$this->published && $this->publishAt !== null && $this->publishAt <= new DateTime()) {
            throw new ValidationException('publishAt must be in the future');
        }
    }
}
```

### 2. OpenAPI Generation from Types

```php
// Generate OpenAPI specs from input types
class OpenApiGenerator
{
    public function generateFromInputType(string $inputClass): array
    {
        $reflection = new ReflectionClass($inputClass);
        $schema = ['type' => 'object', 'properties' => []];
        
        foreach ($reflection->getConstructor()->getParameters() as $param) {
            $inputAttrs = $param->getAttributes(Input::class);
            if (empty($inputAttrs)) {
                continue;
            }
            
            $schema['properties'][$param->getName()] = $this->getPropertySchema($param);
        }
        
        return $schema;
    }
    
    private function getPropertySchema(ReflectionParameter $param): array
    {
        $type = $param->getType();
        
        if ($type instanceof ReflectionNamedType) {
            return match ($type->getName()) {
                'string' => ['type' => 'string'],
                'int' => ['type' => 'integer'],
                'bool' => ['type' => 'boolean'],
                'array' => ['type' => 'array'],
                default => ['$ref' => '#/components/schemas/' . $type->getName()]
            };
        }
        
        return ['type' => 'mixed'];
    }
}
```

## Benefits of Type-Driven Development

### 1. Early Error Detection

```php
// Compile-time error prevention
class OrderService
{
    public function processPayment(OrderId $orderId, Money $amount): PaymentResult
    {
        // Cannot accidentally pass wrong types
        // $this->processPayment($amount, $orderId); // Compile error!
        
        $order = $this->orderRepository->find($orderId);
        
        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }
        
        return $this->paymentService->charge($amount);
    }
}
```

### 2. Refactoring Safety

```php
// When you change a type, all usages must be updated
class UserId
{
    // If you change the internal representation...
    private function __construct(private Uuid $uuid) {} // Changed from string
    
    // All code using UserId must be updated
    public function toString(): string
    {
        return $this->uuid->toString(); // Updated implementation
    }
}

// The compiler ensures all usages are updated
```

### 3. Documentation as Code

```php
// Types serve as living documentation
interface UserRepositoryInterface
{
    /** @return User[] */
    public function findByRole(UserRole $role): array;
    
    public function findByEmail(Email $email): ?User;
    
    /** @return User[] */
    public function search(UserSearchInput $criteria): array;
}

// The interface clearly shows what operations are available
// and what types they expect/return
```

## Conclusion

Type-Driven Development with Ray.InputQuery provides:

1. **Compile-time Safety**: Catch errors before they reach production
2. **Self-Documenting Code**: Types serve as executable documentation
3. **Refactoring Confidence**: Changes propagate safely through the codebase
4. **Better IDE Support**: Enhanced autocompletion and error detection
5. **Testing Benefits**: Types guide test case design and coverage
6. **Domain Clarity**: Business rules are encoded in the type system

This approach leads to more robust, maintainable applications where the type system actively helps prevent bugs and guides development.