<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ReflectionMethod;

/**
 * @template T of object
 * @psalm-type Query = array<string, mixed>
 */
interface InputQueryInterface
{
    /**
     * Get method arguments from query data
     *
     * @param Query $query HTTP request data ($_POST, $_GET, etc.)
     *
     * @return array<mixed>
     */
    public function getArguments(ReflectionMethod $method, array $query): array;

    /**
     * Create object from query data
     *
     * @param class-string<T> $class
     * @param Query           $query HTTP request data ($_POST, $_GET, etc.)
     *
     * @return T
     */
    public function newInstance(string $class, array $query): object;
}
