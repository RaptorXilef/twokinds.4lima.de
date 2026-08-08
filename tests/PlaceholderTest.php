<?php

/**
 * Platzhalter-Test, um sicherzustellen, dass PHPUnit korrekt konfiguriert ist.
 *
 * @since 1.0.0
 *
 * @file tests/PlaceholderTest.php
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
/**
 * Class PlaceholderTest
 *
 * Dient als Initial-Test, damit CI-Pipelines nicht aufgrund fehlender Tests fehlschlagen.
 */
final class PlaceholderTest extends TestCase
{
    /**
     * Prüft, ob das Test-Framework grundsätzlich funktioniert.
     */
    public function testEnvironmentWorks(): void
    {
        $this->assertTrue(true);
    }
}
