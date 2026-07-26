<?php

declare(strict_types=1);

namespace App\Application\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ActionRoute
{
    public function __construct(public string $key)
    {
    }
}
