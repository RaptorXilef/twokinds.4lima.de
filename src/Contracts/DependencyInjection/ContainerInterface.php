<?php

declare(strict_types=1);

namespace App\Contracts\DependencyInjection;

interface ContainerInterface
{
    public function get(string $id): mixed;

    public function bind(string $id, \Closure $resolver): void;
}
