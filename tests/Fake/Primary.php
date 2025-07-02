<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Attribute;
use Ray\Di\Di\Qualifier;

#[Attribute(Attribute::TARGET_PARAMETER)]
#[Qualifier]
final class Primary
{
}
