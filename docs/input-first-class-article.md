# Input as First-Class Citizen: A New Design Philosophy with Ray.InputQuery

## Introduction

When was the last time you thought deeply about how your application handles input? If you're like most developers, you probably treat input as a necessary evil - something to validate, sanitize, and quickly transform into your domain objects.

But what if we've been thinking about input all wrong?

## The Problem with Traditional Input Handling

Let's look at a typical web application endpoint:

```php
public function createArticle(Request $request)
{
    $title = $request->input('title');
    $content = $request->input('content');
    $authorName = $request->input('author_name');
    $authorEmail = $request->input('author_email');
    $tags = $request->input('tags', []);
    
    // Manual validation
    if (empty($title)) {
        throw new ValidationException('Title is required');
    }
    
    if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('Invalid email');
    }
    
    // Manual object construction
    $author = new Author($authorName, $authorEmail);
    $article = new Article($title, $content, $author);
    
    foreach ($tags as $tagName) {
        $article->addTag(new Tag($tagName));
    }
    
    return $this->repository->save($article);
}
```

What's wrong with this code? Everything:

- **No type safety** until deep inside the method
- **Structure is hidden** in implementation details
- **Validation logic is scattered**
- **The relationship between form fields is implicit**

## Enter Ray.InputQuery: Input as First-Class Citizen

Ray.InputQuery introduces a radical idea: **treat input as a first-class citizen** in your application architecture.

### What Does This Mean?

Instead of treating input as raw data to be processed, we define input as structured, typed objects that mirror our forms and API contracts:

```php
final class ArticleInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly AuthorInput $author,
        #[Input] public readonly array $tags = []
    ) {}
}

final class AuthorInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email
    ) {}
}
```

Now our endpoint becomes:

```php
public function createArticle(ArticleInput $input)
{
    return $this->repository->save(
        Article::fromInput($input)
    );
}
```

Behind the scenes, Ray.InputQuery:
1. Analyzes the method parameters
2. Finds the `ArticleInput` parameter with `#[Input]` attribute
3. Creates the object from query data
4. Passes it to your method

## The Magic: From Flat to Structured

Here's where it gets interesting. Your HTML form sends flat data:

```
title=My+Article&content=Lorem+ipsum&author_name=John&author_email=john@example.com
```

Ray.InputQuery automatically transforms this into:

```php
ArticleInput {
    title: "My Article"
    content: "Lorem ipsum"
    author: AuthorInput {
        name: "John"
        email: "john@example.com"
    }
}
```

## Why This Is Revolutionary

### 1. Type Safety at the Boundary

Traditional approaches have a dangerous gap:

```
[Untyped HTTP Request] → [Manual Validation] → [Typed Domain]
         ↑                                           ↑
    Bugs enter here                          Safety starts here
```

With Input classes:

```
[HTTP Request] → [Input Class] → [Domain]
                      ↑
              Type safety starts HERE
```

### 2. Structure Mirrors Intent

Your input classes naturally mirror your forms:

```html
<form>
    <input name="title" required>
    <textarea name="content" required></textarea>
    
    <fieldset>
        <legend>Author Information</legend>
        <input name="author_name" required>
        <input name="author_email" type="email" required>
    </fieldset>
    
    <div class="tags">
        <input name="tags[]" placeholder="Tag 1">
        <input name="tags[]" placeholder="Tag 2">
    </div>
</form>
```

The `ArticleInput` class structure directly reflects this form structure. No mental mapping required!

### 3. Input-Specific Logic

Input classes can contain logic specific to input processing:

```php
final class PasswordChangeInput
{
    public function __construct(
        #[Input] public readonly string $currentPassword,
        #[Input] public readonly string $newPassword,
        #[Input] public readonly string $confirmPassword
    ) {
        if ($newPassword !== $confirmPassword) {
            throw new PasswordMismatchException();
        }
        
        if (strlen($newPassword) < 8) {
            throw new WeakPasswordException();
        }
    }
}
```

This validation logic belongs to the input, not to the domain entity or some separate validator class.

## Real-World Example: E-commerce Checkout

Let's see how this scales to complex scenarios:

```php
final class CheckoutInput
{
    public function __construct(
        #[Input] public readonly CartInput $cart,
        #[Input] public readonly CustomerInput $customer,
        #[Input] public readonly ShippingAddressInput $shipping,
        #[Input] public readonly BillingAddressInput $billing,
        #[Input] public readonly PaymentMethodInput $payment,
        #[Input] public readonly ?string $couponCode = null,
        #[Input] public readonly bool $giftWrap = false,
        #[Input] public readonly ?string $giftMessage = null
    ) {}
}
```

This single class declaration documents:
- What data the checkout process needs
- How that data is structured
- What's optional vs required
- The relationships between different pieces of data

## Integration with Modern Development

### AI-Assisted Development

With clear input structures, AI can help generate code:

```
"Generate a Ray.InputQuery class for this HTML form: [paste form HTML]"
```

### JSON Schema Integration (Future)

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "title": {
      "type": "string",
      "minLength": 1,
      "maxLength": 200
    },
    "author": {
      "$ref": "#/definitions/AuthorInput"
    }
  }
}
```

One structure, multiple representations: PHP classes, JSON Schema, TypeScript types, API documentation.

### Frontend-Backend Contract

```typescript
// Auto-generated from PHP Input classes
interface ArticleInput {
    title: string;
    content: string;
    author: AuthorInput;
    tags: string[];
}
```

## The Paradigm Shift

This isn't just about a new way to handle form data. It's about recognizing that **input is a fundamental concern** of web applications that deserves first-class treatment.

Traditional thinking:
- "How do I validate this data?"
- "How do I transform this array into objects?"
- "Where should I put this validation logic?"

Input-first thinking:
- "What is the structure of this input?"
- "What constraints are inherent to this input?"
- "How does this input relate to my domain?"

## Getting Started

```bash
composer require ray/input-query
```

Define your first input class:

```php
final class ContactInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly string $message
    ) {}
}
```

Use it in your application:

```php
// Method that accepts input
public function contact(ContactInput $input): Response
{
    $this->mailer->send(new ContactEmail($input));
    return new Response('Message sent!');
}

// Ray.InputQuery generates the argument
$inputQuery = new InputQuery($injector);
$method = new ReflectionMethod($this, 'contact');
$args = $inputQuery->getArguments($method, $_POST);

// Call the method
$response = $this->contact(...$args);
```

## Conclusion

Ray.InputQuery isn't just another validation library. It's a fundamental rethinking of how we handle input in web applications. By treating input as a first-class citizen, we can:

- Catch errors at the system boundary
- Write more maintainable code
- Create better developer experiences
- Build more reliable applications

The next time you write `$request->input('field')`, ask yourself: "What if this input had a proper home?"

Welcome to the world where input is a first-class citizen.

---

*Ray.InputQuery is part of the Ray.* family of libraries, focusing on clean, type-safe PHP development. Learn more at [https://github.com/ray-di/Ray.InputQuery](https://github.com/ray-di/Ray.InputQuery)*