<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\InputQuery\Attribute\Input;

final class UserProfile
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly int $age = 25,
        #[Input] public readonly ?string $bio = null,
        #[Input] public readonly bool $isPublic = true
    ) {}

    public function getDisplayInfo(): string
    {
        $visibility = $this->isPublic ? 'Public' : 'Private';
        $bio = $this->bio ?? 'No bio available';
        
        return sprintf(
            "Name: %s\nEmail: %s\nAge: %d\nBio: %s\nProfile: %s",
            $this->name,
            $this->email,
            $this->age,
            $bio,
            $visibility
        );
    }
}