<?php

declare(strict_types=1);

namespace App\Application\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Route
{
    public function __construct(
        public string $method,
        public string $path,
    ) {
    }
}
