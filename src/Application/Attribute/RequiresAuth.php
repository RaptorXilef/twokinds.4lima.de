<?php

declare(strict_types=1);

namespace App\Application\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RequiresAuth
{
}
