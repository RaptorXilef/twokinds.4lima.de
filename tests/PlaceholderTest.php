<?php

/**
 * Platzhalter-Test, um sicherzustellen, dass PHPUnit korrekt konfiguriert ist.
 *
 * @file tests/PlaceholderTest.php
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Class PlaceholderTest
 *
 * Dient als Initial-Test, damit CI-Pipelines nicht aufgrund fehlender Tests fehlschlagen.
 */
class PlaceholderTest extends TestCase
{
    /**
     * Prüft, ob das Test-Framework grundsätzlich funktioniert.
     *
     * @return void
     */
    public function testEnvironmentWorks(): void
    {
        $this->assertTrue(true);
    }
}
