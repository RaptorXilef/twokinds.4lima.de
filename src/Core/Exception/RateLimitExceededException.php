<?php

declare(strict_types=1);

namespace App\Core\Exception;

use DomainException;

final class RateLimitExceededException extends DomainException
{
}
