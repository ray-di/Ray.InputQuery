# Input as First-Class Citizen

## Treating Input as an Independent Domain

In traditional web application development, input has been treated as merely a "transit point." Ray.InputQuery introduces a revolutionary approach that recognizes **input as an independent domain** deserving its own abstraction.

## How It Differs from Traditional Patterns

### Entity
```php
class User {
    private $id;
    private $name;
    public function changeName($name) { ... }  // Business logic
}
```
Persisted business objects with identity and behavior.

### DTO (Data Transfer Object)
```php
class UserDto {
    public $id;
    public $name;
    // No logic, used for both input and output
}
```
Data carriers that don't express structure or intent.

### Input-Only Classes (The Revolutionary Approach)
```php
final class UserRegistrationInput {
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly PasswordInput $password,
        #[Input] public readonly bool $agreeToTerms  // UI state included!
    ) {}
}
```

## Characteristics of Input-Only Classes

### 1. **Unidirectional**
From external sources (forms, APIs) to internal application only. Never used for output.

### 2. **Structure Expression**
```php
final class CheckoutInput {
    public function __construct(
        #[Input] public readonly CartInput $cart,
        #[Input] public readonly ShippingAddressInput $shipping,
        #[Input] public readonly PaymentMethodInput $payment,
        #[Input] public readonly ?string $couponCode = null
    ) {}
}
```
This mirrors the checkout screen structure exactly!

### 3. **Temporality**
Not persisted. Exists only at the entry point and disappears after processing.

### 4. **Input-Specific Expression**
```php
final class PasswordInput {
    public function __construct(
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm  // Only needed during input
    ) {}
}
```
`passwordConfirm` is unnecessary in entities but essential during input. This distinction is naturally expressed.

## Why This Is Revolutionary

### 1. **Extending Type Boundaries**

Traditional:
```
[Untyped Zone] → Manual Conversion → [Typed Zone]
$_POST           Validation           Entity
```

Ray.InputQuery:
```
[Typed Zone ←→ Typed Zone ←→ Typed Zone]
Form         Input Class    Entity
```

Type safety extended to the system's outermost edges!

### 2. **Declarative Programming**

Imperative (Traditional):
```php
$title = $_POST['title'] ?? '';
$authorName = $_POST['author_name'] ?? '';
if (empty($title)) {
    throw new ValidationException('Title required');
}
$author = new Author($authorName, $_POST['author_email']);
$todo = new Todo($title, $author);
```

Declarative (Ray.InputQuery):
```php
final class TodoInput {
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly AuthorInput $author
    ) {}
}
```

**From "how to transform" to "what it should be"**

### 3. **Natural Alignment with Web Structure**

HTTP Request:
```
POST /articles/123/comments
author_name=John&author_email=john@example.com&content=Great!
```

Input Class:
```php
final class CommentInput {
    public function __construct(
        #[Input] public readonly string $content,
        #[Input] public readonly AuthorInput $author
    ) {}
}
```

**Web's fundamental structure (query/form data) directly becomes objects!**

### 4. **Form and Code Integration**

HTML:
```html
<form>
    <fieldset>
        <legend>Shipping Address</legend>
        <input name="shipping_street">
        <input name="shipping_city">
    </fieldset>
</form>
```

PHP:
```php
final class OrderInput {
    public function __construct(
        #[Input] public readonly ShippingAddressInput $shipping
    ) {}
}
```

**HTML structure and PHP structure match perfectly!**

## The Trinity Design (Future)

```
     Input Class (PHP)
         ／  ＼
        ／    ＼
    Form    JSON Schema
   (HTML)  (Specification)
```

- **Input Class**: Type-safe implementation
- **HTML Form**: User interface
- **JSON Schema**: Specification and validation

All express the same structure in different formats.

## Revolutionary Development Flow

### Current
```
Developer looks at form
    ↓
Manually writes validation code
    ↓
Manually writes mapping code
```

### Ray.InputQuery
```
Define form
    ↓
Define Input class (declare structure)
    ↓
Automatically transformed
```

### Future (AI Era)
```
HTML Form
    ↓
AI: "Generate Input class for this form"
    ↓
Done!
```

## New Relationship Between Data and Behavior

```php
final class OrderProcessInput {
    public function __construct(
        // Data (external input)
        #[Input] public readonly string $orderId,
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ?string $couponCode,
        
        // Services (internal capabilities)
        private OrderService $orderService,
        private NotificationService $notifier
    ) {}
    
    public function process(): OrderResult {
        // Combining input data with services
        $order = $this->orderService->create($this->orderId, $this->customer);
        
        if ($this->couponCode) {
            $order->applyCoupon($this->couponCode);
        }
        
        $this->notifier->notify($order);
        return $order;
    }
}
```

**Treating input not just as data, but as objects with processing capabilities!**

## Summary: The Paradigm Shift

Ray.InputQuery fundamentally changes traditional development approaches in the following ways:

1. **Input Independence**: Recognizing input as an independent domain
2. **Structural Alignment**: Forms, code, and specifications share the same structure
3. **Type Extension**: Extending type safety to external boundaries
4. **Declarative Design**: Focus on "what" and automate "how"

This is not just a convenient tool, but a proposal for **a new way of thinking in web application development**.

The shift from "getting data from forms" to "defining input structure" opens the path to better web application development.
