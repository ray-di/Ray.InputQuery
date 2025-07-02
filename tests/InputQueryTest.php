<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;

final class InputQueryTest extends TestCase
{
    protected InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery();
    }

    public function testIsInstanceOfInputQuery(): void
    {
        $actual = $this->inputQuery;
        $this->assertInstanceOf(InputQuery::class, $actual);
    }
}
