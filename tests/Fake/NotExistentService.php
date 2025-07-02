<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

// Interface that has no implementation bound in DI
interface NotExistentService
{
    public function doSomething(): string;
}