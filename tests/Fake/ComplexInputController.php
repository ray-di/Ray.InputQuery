<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class ComplexInputController
{
    /**
     * Test union type parameter without Input attribute
     */
    public function processUnionTypeNoInput(string|int $value = 'default'): void
    {
        // Union type without #[Input] should use default value
    }

    /**
     * Test nullable parameter with Input attribute
     */
    public function processNullableParam(#[Input] ?string $optional): void
    {
        // Nullable parameter handling
    }

    /**
     * Test mixed parameters (no type hint)
     */
    public function processMixedType(#[Input] $data): void
    {
        // No type hint parameter
    }

    /**
     * Test nested object extraction patterns
     */
    public function processNestedExtraction(#[Input] UserInput $user): void
    {
        // Should handle nested query extraction patterns like user_name -> UserInput->name
    }

    /**
     * Test array with complex nested objects
     */
    public function processComplexArray(#[Input(item: UserInput::class)] array $users): void
    {
        // Array of complex objects
    }

    /**
     * Test scalar conversions
     */
    public function processScalarConversions(
        #[Input] string $text,
        #[Input] int $number,
        #[Input] float $decimal,
        #[Input] bool $flag
    ): void {
        // Various scalar type conversions
    }

    /**
     * Test string array processing
     */
    public function processStringArray(#[Input] array $items): void
    {
        // Test array of strings without item type specified
    }

    /**
     * Test default parameter value extraction
     */
    public function processWithDefaults(
        #[Input] string $required,
        string $optional = 'default_value'
    ): void {
        // Test parameter with default but without Input attribute
    }

    /**
     * Test int array processing without item type
     */
    public function processIntArray(#[Input] array $numbers): void
    {
        // Test array of primitive int types without item specification
    }

    /**
     * Test parameter that requires default value
     */
    public function processRequiresDefault(string $param): void
    {
        // Test parameter without Input and without default - should get null
    }
}