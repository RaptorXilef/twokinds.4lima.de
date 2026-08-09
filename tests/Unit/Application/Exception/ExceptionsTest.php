<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exception;

use App\Application\Exception\ValidationException;
use App\Core\Exception\EntityNotFoundException;
use App\Core\Exception\RateLimitExceededException;
use DomainException;

\uses()->group('application', 'exception');

\it('ValidationException can be created via withMessage', function (): void {
    $ex = ValidationException::withMessage('Fehler!');
    \expect($ex)->toBeInstanceOf(ValidationException::class)
        ->and($ex->getMessage())->toBe('Fehler!');
})->covers(ValidationException::class);

\it('EntityNotFoundException extends DomainException', function (): void {
    $ex = new EntityNotFoundException('Not found');
    \expect($ex)->toBeInstanceOf(DomainException::class);
})->covers(EntityNotFoundException::class);

\it('RateLimitExceededException extends DomainException', function (): void {
    $ex = new RateLimitExceededException('Blocked');
    \expect($ex)->toBeInstanceOf(DomainException::class);
})->covers(RateLimitExceededException::class);
