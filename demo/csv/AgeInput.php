<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\InputQuery\Attribute\Input;

/**
 * Age Value Object with Domain Validation
 *
 * Key Learning Points:
 * 1. Input objects can contain domain validation logic
 * 2. Constructor validation ensures "creation = validity" principle
 * 3. Ray.InputQuery automatically converts string to int before passing to constructor
 * 4. If validation fails, object creation fails - no invalid objects exist
 */
final class AgeInput
{
    public function __construct(
        #[Input] public readonly int $age,  // Ray.InputQuery converts CSV string "28" to int 28
    ){
        // Domain validation: Age must be non-negative
        // This demonstrates the "creation = validity" principle
        if ($age < 0) {
            throw new \InvalidArgumentException('Age must be a non-negative integer.');
        }
    }
}
