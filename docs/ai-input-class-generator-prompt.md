# AI Input Class Generator Prompt

## Universal Prompt for Generating Ray.InputQuery Input Classes

Use this prompt with AI assistants to automatically generate appropriate Input classes from various structured formats (HTML forms, JSON schemas, ALPS profiles, etc.).

---

## The Prompt

```
You are an expert PHP developer specializing in Ray.InputQuery. Generate Input classes from the provided structured data format.

## Input Class Design Rules

1. **Use #[Input] attribute on parameters that come from external data**
2. **Create separate classes for logical groupings of fields**
3. **Use readonly properties for immutability**
4. **Apply proper PHP typing (string, int, bool, array, nullable types)**
5. **Convert field names to camelCase for property names**
6. **Group related fields into nested Input objects**
7. **Use appropriate default values for optional fields**
8. **Include @psalm-type annotations for complex arrays**
9. **Add @param documentation for all constructor parameters**
10. **Use Psalm domain types for precise type constraints**

## Naming Conventions

- Class names: PascalCase ending with "Input" (e.g., `UserInput`, `PaymentMethodInput`)
- Property names: camelCase (e.g., `firstName`, `emailAddress`)
- Convert snake_case, kebab-case to camelCase
- Keep semantic meaning clear and concise

## Structure Guidelines

- **Flat fields** → Direct properties with #[Input]
- **Grouped fields** (same prefix/logical group) → Nested Input class
- **Arrays** → Use `array` type with @psalm-type for typed arrays
- **Optional fields** → Use nullable types or default values
- **Validation logic** → Include in constructor if business-rule related

## Documentation Standards

- Add comprehensive @param documentation
- Use @psalm-type for complex array structures
- Use Psalm domain types for precise constraints (e.g., `positive-int`, `non-empty-string`, `int-range<1,12>`)
- Include class-level PHPDoc explaining the Input's purpose
- Document validation rules and constraints

## Required Information

**You MUST provide:**
- **Title**: A clear, descriptive title for what these Input classes represent (e.g., "User Registration Form", "E-commerce Checkout Process", "Blog Post Creation")

**You MAY provide (if helpful):**
- **Description**: Additional context about the purpose, business rules, or special requirements for these Input classes

## Psalm Domain Types Examples

Use precise Psalm types for better documentation and type safety:

### Numeric Constraints
```php
/**
 * @param positive-int        $quantity Product quantity (must be > 0)
 * @param int-range<1,12>     $month    Month (1-12)
 * @param int-range<1900,2100> $year   Year range
 * @param float-range<0.0,100.0> $discountRate Discount percentage (0-100%)
 * @param int-range<0,150>    $age      Person age
 * @param positive-int        $port     Network port number
 */
```

### String Constraints  
```php
/**
 * @param non-empty-string    $title       Article title (cannot be empty)
 * @param non-empty-string    $email       Email address
 * @param non-empty-string    $password    Password (min length handled in constructor)
 * @param lowercase-string    $username    Username (normalized to lowercase)
 * @param numeric-string      $phoneNumber Phone number (digits only)
 */
```

### Array Constraints
```php
/**
 * @param non-empty-array<string>     $tags        At least one tag required
 * @param array<positive-int>         $productIds  Product IDs (all positive)
 * @param list<non-empty-string>      $categories  Ordered list of category names
 */
```

### URL and Format Constraints
```php
/**
 * @psalm-type EmailAddress = non-empty-string
 * @psalm-type Url = non-empty-string
 * @psalm-type PhoneNumber = numeric-string
 * @psalm-type CountryCode = non-empty-string  // Could be more specific: 'US'|'CA'|'JP'
 * @psalm-type CurrencyCode = 'USD'|'EUR'|'JPY'|'GBP'
 * @psalm-type Priority = 'low'|'medium'|'high'|'urgent'
 */
```

## Two-Phase Approach

**Phase 1: Generate a comprehensive flat Input class with all fields**
**Phase 2: Propose hierarchical refactoring with nested Input classes**

This allows you to see both the complete picture and the organized structure.

## Example Output Format with Psalm Domain Types

```php
<?php

declare(strict_types=1);

use Ray\InputQuery\Attribute\Input;

/**
 * E-commerce product input data
 * 
 * @psalm-type ProductCategory = 'electronics'|'clothing'|'books'|'home'
 * @psalm-type CurrencyCode = 'USD'|'EUR'|'JPY'|'GBP'
 * @psalm-type ProductStatus = 'draft'|'active'|'discontinued'
 */
final class ProductInput
{
    /**
     * @param non-empty-string                    $name            Product name (required, cannot be empty)
     * @param non-empty-string                    $sku             Product SKU (unique identifier)
     * @param positive-int                        $quantity        Available quantity (must be > 0)
     * @param float                               $price           Product price (positive number)
     * @param CurrencyCode                        $currency        Currency code
     * @param ProductCategory                     $category        Product category
     * @param ProductStatus                       $status          Product status
     * @param int-range<0,100>                    $discountPercent Discount percentage (0-100%)
     * @param positive-int                        $weight          Weight in grams
     * @param non-empty-array<non-empty-string>   $tags            Product tags (at least one required)
     */
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $sku,
        #[Input] public readonly int $quantity,
        #[Input] public readonly float $price,
        #[Input] public readonly string $currency = 'USD',
        #[Input] public readonly string $category = 'electronics',
        #[Input] public readonly string $status = 'draft',
        #[Input] public readonly int $discountPercent = 0,
        #[Input] public readonly int $weight = 1,
        #[Input] public readonly array $tags = ['general']
    ) {
        if ($this->price <= 0) {
            throw new \InvalidArgumentException('Price must be positive');
        }
        
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }
}

/**
 * User registration input with precise constraints
 * 
 * @psalm-type UserRole = 'user'|'admin'|'moderator'
 * @psalm-type CountryCode = 'US'|'CA'|'JP'|'UK'|'DE'|'FR'
 */
final class UserRegistrationInput
{
    /**
     * @param non-empty-string      $email       User's email address
     * @param non-empty-string      $password    User's password (will be hashed)  
     * @param non-empty-string      $firstName   User's first name
     * @param non-empty-string      $lastName    User's last name
     * @param int-range<13,120>     $age         User's age (13-120 years)
     * @param CountryCode           $country     User's country
     * @param UserRole              $role        User's role in system
     * @param numeric-string|null   $phoneNumber Phone number (digits only, optional)
     */
    public function __construct(
        #[Input] public readonly string $email,
        #[Input] public readonly string $password,
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly int $age,
        #[Input] public readonly string $country,
        #[Input] public readonly string $role = 'user',
        #[Input] public readonly ?string $phoneNumber = null
    ) {
        if (strlen($this->password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }
        
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }
}
```

## Real-World Domain Type Examples

### Payment Processing
```php
/**
 * @psalm-type CardType = 'visa'|'mastercard'|'amex'|'discover'
 * @psalm-type CurrencyAmount = positive-int  // Amount in cents
 * 
 * @param non-empty-string       $cardNumber      Credit card number
 * @param int-range<1,12>        $expiryMonth     Expiry month (1-12)
 * @param int-range<2024,2040>   $expiryYear      Expiry year
 * @param int-range<100,9999>    $cvv             Card verification value
 * @param CurrencyAmount         $amount          Payment amount in cents
 * @param CardType               $cardType        Detected card type
 */
```

### Content Management
```php
/**
 * @psalm-type ContentStatus = 'draft'|'review'|'published'|'archived'
 * @psalm-type Priority = 'low'|'normal'|'high'|'urgent'
 * 
 * @param non-empty-string                   $title     Content title
 * @param non-empty-string                   $slug      URL slug (lowercase, hyphenated)
 * @param ContentStatus                      $status    Publication status
 * @param Priority                           $priority  Content priority
 * @param positive-int                       $authorId  Author's user ID
 * @param non-empty-array<non-empty-string>  $tags      Content tags
 */
```

### Customer Feedback Form Example
```php
/**
 * @psalm-type SatisfactionRating = int-range<1,5>
 * 
 * @param non-empty-string|null   $customerName        任意の顧客名
 * @param SatisfactionRating      $satisfactionRating  満足度（1〜5）
 * @param non-empty-string        $feedbackComment     フィードバック内容
 * @param bool                    $anonymous           匿名投稿かどうか
 */
```

### API Rate Limiting
```php
/**
 * @psalm-type HttpMethod = 'GET'|'POST'|'PUT'|'DELETE'|'PATCH'
 * @psalm-type RateLimit = int-range<1,10000>  // Requests per hour
 * 
 * @param non-empty-string   $apiKey            API key
 * @param HttpMethod         $method            HTTP method
 * @param RateLimit          $requestsPerHour   Rate limit
 * @param positive-int       $retryAfterSeconds Retry delay
 */
```

### Geographic Data
```php
/**
 * @psalm-type Latitude = float-range<-90.0,90.0>
 * @psalm-type Longitude = float-range<-180.0,180.0>
 * @psalm-type ZipCode = numeric-string  // US ZIP codes
 * 
 * @param Latitude           $latitude  Geographic latitude
 * @param Longitude          $longitude Geographic longitude
 * @param ZipCode            $zipCode   Postal code
 * @param int-range<1,50>    $radius    Search radius in miles
 */
```
