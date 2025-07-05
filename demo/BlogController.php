<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\Di\Di\Named;
use Ray\InputQuery\Attribute\Input;

interface LoggerInterface
{
    public function log(string $message): void;
}

final class ConsoleLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo '[LOG] ' . $message . "\n";
    }
}

final class BlogController
{
    public function createPost(
        #[Input]
        BlogPost $post,
        ConsoleLogger $logger,
        #[Named('app.version')]
        string $version,
    ): string {
        $logger->log("Creating blog post: {$post->title} (App v{$version})");

        return "Blog post created successfully!\n\n" . $post->getPostSummary();
    }

    public function updateProfile(
        #[Input]
        UserProfile $profile,
        ConsoleLogger $logger,
    ): string {
        $logger->log("Updating user profile: {$profile->name}");

        return "Profile updated successfully!\n\n" . $profile->getDisplayInfo();
    }
}
