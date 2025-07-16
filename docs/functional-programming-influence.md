# Functional Programming Influence in Ray.InputQuery

## Introduction

Ray.InputQuery incorporates several functional programming concepts that enhance code reliability, predictability, and maintainability. While PHP is primarily an object-oriented language, Ray.InputQuery demonstrates how functional programming principles can be successfully applied to create more robust web applications.

## Core Functional Programming Concepts

### 1. Immutability

#### Immutable Input Objects

```php
// All input objects are immutable by design
class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly int $age,
    ) {}
    
    // No setter methods - object cannot be modified after creation
    // readonly properties enforce immutability at the language level
}

// Traditional mutable approach (avoided)
class MutableUserInput
{
    public string $name;
    public string $email;
    public int $age;
    
    // Problematic: object state can change unexpectedly
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

#### Immutable Domain Objects

```php
class User
{
    public function __construct(
        public readonly UserId $id,
        public readonly string $name,
        public readonly Email $email,
        public readonly DateTime $createdAt,
    ) {}
    
    // Instead of mutation, return new instances
    public function changeName(string $newName): User
    {
        return new User(
            $this->id,
            $newName,           // Changed field
            $this->email,       // Preserved fields
            $this->createdAt,
        );
    }
    
    public function changeEmail(Email $newEmail): User
    {
        return new User(
            $this->id,
            $this->name,
            $newEmail,          // Changed field
            $this->createdAt,
        );
    }
}
```

### 2. Pure Functions

#### ToArray as a Pure Function

```php
class ToArray implements ToArrayInterface
{
    /**
     * Pure function: same input always produces same output
     * No side effects, no external dependencies
     */
    public function __invoke(object $input): array
    {
        return $this->extractProperties($input);
    }
    
    /**
     * Pure helper function
     */
    private function extractProperties(object $object): array
    {
        // No mutations, no side effects
        // Only reads data and returns new data structure
        $result = [];
        $reflection = new ReflectionClass($object);
        
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($object);
            $name = $property->getName();
            
            if (is_object($value)) {
                // Recursive pure function call
                $nestedProperties = $this->extractProperties($value);
                foreach ($nestedProperties as $nestedName => $nestedValue) {
                    $result[$nestedName] = $nestedValue;
                }
            } else {
                $result[$name] = $value;
            }
        }
        
        return $result;
    }
}
```

#### Pure Transformation Functions

```php
class UserTransformer
{
    /**
     * Pure function: transforms input to domain object
     */
    public static function fromInput(CreateUserInput $input): User
    {
        return new User(
            UserId::generate(),
            $input->name,
            $input->email,
            new DateTime()
        );
    }
    
    /**
     * Pure function: transforms domain object to array
     */
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->id->toString(),
            'name' => $user->name,
            'email' => $user->email->getValue(),
            'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

### 3. Function Composition

#### Composing Transformations

```php
class DataPipeline
{
    public function __construct(
        private InputQueryInterface $inputQuery,
        private ToArrayInterface $toArray,
        private ValidatorInterface $validator,
    ) {}
    
    /**
     * Compose multiple pure functions into a pipeline
     */
    public function processUserCreation(array $httpData): array
    {
        return $this->compose(
            fn($data) => $this->inputQuery->newInstance(CreateUserInput::class, $data),
            fn($input) => $this->validator->validate($input),
            fn($input) => UserTransformer::fromInput($input),
            fn($user) => $this->userRepository->save($user),
            fn($user) => UserTransformer::toArray($user),
        )($httpData);
    }
    
    /**
     * Generic function composition utility
     */
    private function compose(callable ...$functions): callable
    {
        return fn($input) => array_reduce(
            $functions,
            fn($carry, $fn) => $fn($carry),
            $input
        );
    }
}
```

#### Pipeline Pattern

```php
class OrderProcessingPipeline
{
    public function process(CreateOrderInput $input): OrderResult
    {
        return Pipeline::of($input)
            ->through($this->validateInventory(...))
            ->through($this->calculatePricing(...))
            ->through($this->applyDiscounts(...))
            ->through($this->reserveStock(...))
            ->through($this->createOrder(...))
            ->through($this->sendConfirmation(...))
            ->get();
    }
    
    private function validateInventory(CreateOrderInput $input): CreateOrderInput
    {
        foreach ($input->items as $item) {
            if (!$this->inventory->isAvailable($item->productId, $item->quantity)) {
                throw new InsufficientStockException($item->productId);
            }
        }
        
        return $input; // Immutable passthrough
    }
    
    private function calculatePricing(CreateOrderInput $input): CreateOrderInput
    {
        // Return new input with calculated pricing
        return new CreateOrderInput(
            $input->customerId,
            $this->pricingService->calculateItemPrices($input->items),
            $input->shippingAddress
        );
    }
}
```

### 4. Higher-Order Functions

#### Functions that Return Functions

```php
class ValidationBuilder
{
    /**
     * Higher-order function that returns a validator function
     */
    public static function required(string $fieldName): callable
    {
        return function($value) use ($fieldName) {
            if (empty($value)) {
                throw new ValidationException("Field '{$fieldName}' is required");
            }
            return $value;
        };
    }
    
    /**
     * Higher-order function for length validation
     */
    public static function length(int $min, int $max): callable
    {
        return function($value) use ($min, $max) {
            $length = strlen($value);
            if ($length < $min || $length > $max) {
                throw new ValidationException("Length must be between {$min} and {$max}");
            }
            return $value;
        };
    }
    
    /**
     * Combine multiple validators
     */
    public static function all(callable ...$validators): callable
    {
        return function($value) use ($validators) {
            foreach ($validators as $validator) {
                $value = $validator($value);
            }
            return $value;
        };
    }
}

// Usage
class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
    ) {
        // Functional validation composition
        $nameValidator = ValidationBuilder::all(
            ValidationBuilder::required('name'),
            ValidationBuilder::length(2, 50)
        );
        
        $this->name = $nameValidator($name);
        $this->email = filter_var($email, FILTER_VALIDATE_EMAIL) ?: throw new ValidationException('Invalid email');
    }
}
```

#### Map, Filter, Reduce Patterns

```php
class CollectionProcessor
{
    /**
     * Functional processing of input collections
     */
    public function processOrderItems(array $items): array
    {
        return array_map(
            fn($item) => $this->inputQuery->newInstance(OrderItemInput::class, $item),
            array_filter(
                $items,
                fn($item) => isset($item['productId']) && isset($item['quantity'])
            )
        );
    }
    
    /**
     * Reduce pattern for aggregation
     */
    public function calculateTotal(array $orderItems): Money
    {
        return array_reduce(
            $orderItems,
            fn(Money $total, OrderItemInput $item) => $total->add($item->getPrice()),
            Money::zero(Currency::USD)
        );
    }
    
    /**
     * Functional error collection
     */
    public function validateAll(array $inputs): ValidationResult
    {
        $errors = [];
        $validInputs = [];
        
        foreach ($inputs as $index => $inputData) {
            try {
                $validInputs[] = $this->inputQuery->newInstance(ItemInput::class, $inputData);
            } catch (ValidationException $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        return new ValidationResult($validInputs, $errors);
    }
}
```

### 5. Monadic Patterns

#### Maybe/Option Pattern

```php
class Maybe
{
    private function __construct(private mixed $value) {}
    
    public static function of(mixed $value): self
    {
        return new self($value);
    }
    
    public static function none(): self
    {
        return new self(null);
    }
    
    public function map(callable $fn): self
    {
        return $this->value === null ? self::none() : self::of($fn($this->value));
    }
    
    public function flatMap(callable $fn): self
    {
        return $this->value === null ? self::none() : $fn($this->value);
    }
    
    public function getOrElse(mixed $default): mixed
    {
        return $this->value ?? $default;
    }
    
    public function isSome(): bool
    {
        return $this->value !== null;
    }
}

// Usage in service layer
class UserService
{
    public function findUserByEmail(Email $email): Maybe
    {
        $user = $this->repository->findByEmail($email);
        return $user ? Maybe::of($user) : Maybe::none();
    }
    
    public function getUserProfile(Email $email): ?array
    {
        return $this->findUserByEmail($email)
            ->map(fn($user) => UserTransformer::toArray($user))
            ->map(fn($array) => $this->enrichWithProfile($array))
            ->getOrElse(null);
    }
}
```

#### Result Pattern for Error Handling

```php
abstract class Result
{
    abstract public function isSuccess(): bool;
    abstract public function isFailure(): bool;
    abstract public function map(callable $fn): Result;
    abstract public function flatMap(callable $fn): Result;
}

class Success extends Result
{
    public function __construct(private mixed $value) {}
    
    public function isSuccess(): bool { return true; }
    public function isFailure(): bool { return false; }
    
    public function map(callable $fn): Result
    {
        try {
            return new Success($fn($this->value));
        } catch (Exception $e) {
            return new Failure($e);
        }
    }
    
    public function flatMap(callable $fn): Result
    {
        try {
            return $fn($this->value);
        } catch (Exception $e) {
            return new Failure($e);
        }
    }
    
    public function getValue(): mixed
    {
        return $this->value;
    }
}

class Failure extends Result
{
    public function __construct(private Exception $error) {}
    
    public function isSuccess(): bool { return false; }
    public function isFailure(): bool { return true; }
    
    public function map(callable $fn): Result { return $this; }
    public function flatMap(callable $fn): Result { return $this; }
    
    public function getError(): Exception
    {
        return $this->error;
    }
}

// Usage
class OrderService
{
    public function createOrder(CreateOrderInput $input): Result
    {
        return $this->validateStock($input)
            ->flatMap(fn($input) => $this->processPayment($input))
            ->flatMap(fn($input) => $this->saveOrder($input))
            ->map(fn($order) => $this->sendConfirmation($order));
    }
    
    private function validateStock(CreateOrderInput $input): Result
    {
        try {
            $this->stockValidator->validate($input);
            return new Success($input);
        } catch (StockException $e) {
            return new Failure($e);
        }
    }
}
```

## Functional Data Transformations

### 1. Immutable Data Structures

```php
class ImmutableList
{
    private function __construct(private array $items) {}
    
    public static function of(array $items): self
    {
        return new self($items);
    }
    
    public static function empty(): self
    {
        return new self([]);
    }
    
    public function add(mixed $item): self
    {
        return new self([...$this->items, $item]);
    }
    
    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->items));
    }
    
    public function filter(callable $predicate): self
    {
        return new self(array_filter($this->items, $predicate));
    }
    
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }
    
    public function toArray(): array
    {
        return $this->items;
    }
}

// Usage
class BatchProcessor
{
    public function processUserInputs(array $rawInputs): ImmutableList
    {
        return ImmutableList::of($rawInputs)
            ->filter(fn($input) => $this->isValid($input))
            ->map(fn($input) => $this->inputQuery->newInstance(UserInput::class, $input))
            ->map(fn($input) => $this->userService->createUser($input));
    }
}
```

### 2. Lens Pattern for Deep Updates

```php
class Lens
{
    public function __construct(
        private callable $getter,
        private callable $setter,
    ) {}
    
    public static function property(string $property): self
    {
        return new self(
            fn($obj) => $obj->$property,
            fn($obj, $value) => new $obj(...array_merge(
                get_object_vars($obj),
                [$property => $value]
            ))
        );
    }
    
    public function get(object $obj): mixed
    {
        return ($this->getter)($obj);
    }
    
    public function set(object $obj, mixed $value): object
    {
        return ($this->setter)($obj, $value);
    }
    
    public function modify(object $obj, callable $fn): object
    {
        return $this->set($obj, $fn($this->get($obj)));
    }
    
    public function compose(Lens $other): self
    {
        return new self(
            fn($obj) => $other->get($this->get($obj)),
            fn($obj, $value) => $this->set($obj, $other->set($this->get($obj), $value))
        );
    }
}

// Usage for nested updates
class UserProfileService
{
    private Lens $nameLens;
    private Lens $emailLens;
    
    public function __construct()
    {
        $this->nameLens = Lens::property('name');
        $this->emailLens = Lens::property('email');
    }
    
    public function updateUserName(User $user, string $newName): User
    {
        return $this->nameLens->set($user, $newName);
    }
    
    public function capitalizeUserName(User $user): User
    {
        return $this->nameLens->modify($user, fn($name) => ucwords($name));
    }
}
```

## Benefits of Functional Approach

### 1. Predictability

```php
// Functional approach - predictable behavior
class PricingCalculator
{
    /**
     * Pure function - same inputs always produce same outputs
     */
    public function calculatePrice(Product $product, Quantity $quantity, Discount $discount): Money
    {
        $basePrice = $product->getPrice()->multiply($quantity->getValue());
        return $discount->apply($basePrice);
    }
}

// Usage is predictable
$calculator = new PricingCalculator();
$price1 = $calculator->calculatePrice($product, $quantity, $discount);
$price2 = $calculator->calculatePrice($product, $quantity, $discount);
// $price1 always equals $price2
```

### 2. Testability

```php
class FunctionalTransformerTest extends TestCase
{
    public function testToArrayTransformation(): void
    {
        $input = new UserInput('John', 'john@example.com', 30);
        $toArray = new ToArray();
        
        // Pure function - easy to test
        $result1 = $toArray($input);
        $result2 = $toArray($input);
        
        $this->assertSame($result1, $result2); // Always same result
        $this->assertSame([
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30
        ], $result1);
    }
    
    public function testImmutability(): void
    {
        $original = new UserInput('John', 'john@example.com', 30);
        $toArray = new ToArray();
        
        $result = $toArray($original);
        
        // Original object unchanged
        $this->assertSame('John', $original->name);
        
        // Modifying result doesn't affect original
        $result['name'] = 'Jane';
        $this->assertSame('John', $original->name);
    }
}
```

### 3. Composability

```php
class ComposableProcessing
{
    public function buildUserProcessor(): callable
    {
        return $this->compose(
            $this->validateInput(...),
            $this->enrichWithDefaults(...),
            $this->createUser(...),
            $this->sendWelcomeEmail(...),
            $this->logUserCreation(...)
        );
    }
    
    public function buildBatchProcessor(): callable
    {
        $singleProcessor = $this->buildUserProcessor();
        
        return fn(array $users) => array_map($singleProcessor, $users);
    }
    
    private function compose(callable ...$functions): callable
    {
        return fn($input) => array_reduce(
            $functions,
            fn($carry, $fn) => $fn($carry),
            $input
        );
    }
}
```

## Limitations and Trade-offs

### 1. Performance Considerations

```php
// Functional approach may create more objects
class ImmutableUser
{
    public function updateEmail(Email $email): self
    {
        // Creates new object on every update
        return new self($this->id, $this->name, $email, $this->createdAt);
    }
}

// Consider mutable approach for high-frequency updates
class MutableUserBuffer
{
    public function updateEmail(Email $email): void
    {
        // In-place update for performance-critical code
        $this->email = $email;
    }
    
    public function toImmutable(): ImmutableUser
    {
        // Convert to immutable when done
        return new ImmutableUser($this->id, $this->name, $this->email, $this->createdAt);
    }
}
```

### 2. PHP Language Limitations

```php
// PHP doesn't have built-in pattern matching
class PaymentProcessor
{
    public function processPayment(Payment $payment): Result
    {
        // Manual pattern matching
        return match ($payment->getType()) {
            PaymentType::CREDIT_CARD => $this->processCreditCard($payment),
            PaymentType::BANK_TRANSFER => $this->processBankTransfer($payment),
            PaymentType::PAYPAL => $this->processPaypal($payment),
            default => new Failure(new UnsupportedPaymentException())
        };
    }
}
```

## Conclusion

Ray.InputQuery successfully incorporates functional programming principles:

1. **Immutability**: Input objects and domain objects are immutable by design
2. **Pure Functions**: ToArray and transformation functions have no side effects
3. **Function Composition**: Data pipelines compose multiple transformations
4. **Type Safety**: Strong types prevent many classes of errors
5. **Predictability**: Same inputs always produce same outputs

These functional principles result in:
- More reliable and predictable code
- Easier testing and debugging
- Better composability and reusability
- Reduced side effects and hidden dependencies
- Improved reasoning about code behavior

The functional approach, combined with Ray.InputQuery's type system, creates a robust foundation for building maintainable web applications.