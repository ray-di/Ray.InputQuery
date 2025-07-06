<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\InputQuery\Attribute\Input;

/**
 * Individual User Input Object
 *
 * Represents a single user record from CSV with validation and type safety.
 * Demonstrates how services can be injected into Input objects.
 */
final class UserInput
{
    public function __construct(
        #[Input]
        public readonly string $name,
        #[Input]
        public readonly string $email,
        #[Input]
        public readonly int $age,           // non-negative integer
        #[Input]
        public readonly AgeInput $ageInput, // validated age input
        #[Input]
        public readonly bool $isActive = true,
        private ?AgeGroup $ageGroup = null,
    ) {
        // 年齢グループに自動登録
        $this->ageGroup?->addAge($this->age);
    }

    public function __toString(): string
    {
        $age = $this->age ? " (Age: {$this->age})" : '';
        $dept = $this->department ? " [{$this->department}]" : '';

        return "{$this->name} <{$this->email}>{$age}{$dept}";
    }
}
