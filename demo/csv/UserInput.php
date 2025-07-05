<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\InputQuery\Attribute\Input;

use function filter_var;

use const FILTER_VALIDATE_EMAIL;

/**
 * Individual User Input Object
 *
 * Represents a single user record from CSV with validation and type safety.
 */
final class UserInput
{
    public function __construct(
        #[Input]
        public readonly string $name,
        #[Input]
        public readonly string $email,
        #[Input]
        public readonly int|null $age = null,
        #[Input]
        public readonly string $department = '',
        #[Input]
        public readonly bool $isActive = true,
    ) {
    }

    public function isValid(): bool
    {
        return ! empty($this->name)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false
            && ($this->age === null || ($this->age >= 0 && $this->age <= 150));
    }

    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }

        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Invalid email format';
        }

        if ($this->age !== null && ($this->age < 0 || $this->age > 150)) {
            $errors[] = 'Age must be between 0 and 150';
        }

        return $errors;
    }

    public function getDisplayName(): string
    {
        $age = $this->age ? " (Age: {$this->age})" : '';
        $dept = $this->department ? " [{$this->department}]" : '';

        return "{$this->name} <{$this->email}>{$age}{$dept}";
    }
}
