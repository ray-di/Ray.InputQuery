<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Ray\InputQuery\Attribute\Input;

final class BlogPost
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly string $content,
        #[Input] public readonly Author $author,
        #[Input] public readonly ?string $category = null,
        #[Input] public readonly bool $published = false
    ) {}

    public function getPostSummary(): string
    {
        $status = $this->published ? 'Published' : 'Draft';
        $category = $this->category ?? 'Uncategorized';
        $preview = substr($this->content, 0, 100) . '...';
        
        return sprintf(
            "Title: %s\nAuthor: %s <%s>\nCategory: %s\nStatus: %s\nPreview: %s",
            $this->title,
            $this->author->name,
            $this->author->email,
            $category,
            $status,
            $preview
        );
    }
}

final class Author
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        #[Input] public readonly string $id = 'unknown'
    ) {}
}