<?php

declare(strict_types=1);

namespace Ray\InputQuery;

interface ToArrayInterface
{
    /**
     * Convert Input object to flat associative array
     *
     * @param object $input Input object with #[Input] attributes
     *
     * @return array<string, mixed> Flat associative array
     */
    public function __invoke(object $input): array;
}
