<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

/**
 * Age Grouping Service - Stateful Singleton Service
 * 
 * Key Learning Points:
 * 1. Services can be injected into Input objects
 * 2. Singleton services maintain state across multiple Input object creations
 * 3. Business logic is separated from Input objects (Single Responsibility)
 * 4. Input objects can collaborate with services during construction
 * 5. Services accumulate knowledge as Input objects are created
 */
final class AgeGroup
{
    /** @var array<string, int> */
    private array $groups = [
        'under_25' => 0,
        '25_35' => 0, 
        '36_50' => 0,
        'over_50' => 0,
    ];

    public function addAge(?int $age): void
    {
        if ($age === null) {
            return;
        }

        if ($age < 25) {
            $this->groups['under_25']++;
        } elseif ($age <= 35) {
            $this->groups['25_35']++;
        } elseif ($age <= 50) {
            $this->groups['36_50']++;
        } else {
            $this->groups['over_50']++;
        }
    }

    /** @return array<string, int> */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getTotalCount(): int
    {
        return array_sum($this->groups);
    }
}