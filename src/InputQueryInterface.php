<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ReflectionMethod;

/** @template T of object */
interface InputQueryInterface
{
    /**
     * Get method arguments from query data
     *
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     */
    public function getArguments(ReflectionMethod $method, array $query): array;


    /**
     * Create object from query data
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $query
     *
     * @return T
     */
    public function create(string $class, array $query): object;

}
