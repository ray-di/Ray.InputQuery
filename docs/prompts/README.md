# How to Use Prompt Files in docs/prompts/

This directory contains prompts for automatic generation of Ray.InputQuery Input classes and usage examples.

## Usage

### input-class-generator.md

- Use this prompt when asking AI to automatically generate Input classes or perform refactoring.
- Helpful when converting existing flat parameter lists into structured Input objects.
- Example:

```html
<!-- Generate Input classes from this HTML form -->
<form>
    <input name="user_name" type="text">
    <input name="user_email" type="email">
    <input name="order_id" type="number">
    <input name="order_total" type="number">
</form>
```

### usage-generator.md

- Use this prompt when asking AI to generate usage examples of Input classes or integration examples with Ray.MediaQuery and BEAR.Resource.
- Helpful when you want to know how to use Input classes or see implementation examples.
- Example:

```php
final class UserRegistrationInput
{
    public function __construct(
        #[Input] public readonly AccountInput $account,
        #[Input] public readonly ProfileInput $profile,
        #[Input] public readonly PreferencesInput $preferences
    ) {}
}

final class AccountInput
{
    public function __construct(
        #[Input] public readonly string $email,
        #[Input] public readonly string $password,
        #[Input] public readonly string $passwordConfirm
    ) {}
}

final class ProfileInput
{
    public function __construct(
        #[Input] public readonly string $firstName,
        #[Input] public readonly string $lastName,
        #[Input] public readonly ?string $displayName = null
    ) {}
}
```

---

These prompts are useful for AI-assisted development support including introduction, utilization, refactoring, and documentation generation for Ray.InputQuery.
