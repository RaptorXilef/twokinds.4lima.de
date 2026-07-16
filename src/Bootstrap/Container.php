<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Bootstrap\Providers\EventServiceProvider;
use App\Bootstrap\Providers\InfrastructureServiceProvider;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Infrastructure\Config\Config;

/**
 * Dependency Injection (DI) Container der Anwendung.
 *
 * Verwaltet den Objekt-Lifecycle durch Lazy Loading. Registriert Infrastruktur-Komponenten,
 * Core-Services und Controller und injiziert benötigte Abhängigkeiten.
 * Kontext: Zentraler Registry- und Inversion-of-Control (IoC) Knotenpunkt der Applikation.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 *
 * @copyright (c) 2026 Felix Maywald. All rights reserved.
 * @license   https://github.com/RaptorXilef/kga-einfahrgenehmigung/blob/main/LICENSE
 *
 * @link      https://github.com/RaptorXilef/kga-einfahrgenehmigung/
 *
 * @author    Felix Maywald (@RaptorXilef)
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, \Closure>
     */
    private array $services = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    public function __construct(private readonly Config $config)
    {
        $this->setup();
    }

    /**
     * Initialisiert den internen Registrierungsprozess.
     * Mappt die Konfigurations-Instanzen und stößt die funktionsspezifischen Register-Methoden an.
     */
    private function setup(): void
    {
        // Basis-Instanzen hart registrieren
        $this->instances[self::class]      = $this; // Verhindert, dass der Container geklont wird!
        $this->instances[Container::class] = $this;

        $this->instances[ContainerInterface::class] = $this; // Interface binden

        // Konfiguration (Wird direkt als Instanz übergeben, da sie schon existiert)
        $this->instances[Config::class]          = $this->config;
        $this->instances[ConfigInterface::class] = $this->config;

        // Provider registrieren
        $providers = [
            new InfrastructureServiceProvider(),
            new EventServiceProvider(),
        ];

        foreach ($providers as $provider) {
            $provider->register($this);
        }
    }

    /**
     * Registriert einen Service im Container.
     *
     * @param string   $id       Der Identifikator (Klassenname oder Interface).
     * @param \Closure $resolver Die Factory-Funktion zur Erstellung der Instanz.
     */
    public function bind(string $id, \Closure $resolver): void
    {
        $this->services[$id] = $resolver;
    }

    /**
     * Löst eine Abhängigkeit auf und liefert eine shared (Singleton) Instanz zurück.
     * Erstellt das Objekt beim ersten Aufruf über die hinterlegte Closure (Lazy Loading)
     * und cached es für nachfolgende Zugriffe im System.
     *
     * @param string $id Die vollqualifizierte Klasse oder der Identifikations-String des Services.
     *
     * @return mixed Die instanziierte Service- oder Controller-Komponente.
     */
    public function get(string $id): mixed
    {
        // 1. Haben wir schon eine fertige Instanz? (Singleton-Verhalten)
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Haben wir ein explizites Binding (Closure aus den Providern)?
        if (isset($this->services[$id])) {
            $this->instances[$id] = ($this->services[$id])();

            return $this->instances[$id];
        }

        // 3. AUTOWIRING: Versuche, die Klasse automatisch aufzulösen!
        if (\class_exists($id)) {
            $this->instances[$id] = $this->autowire($id);

            return $this->instances[$id];
        }

        throw new \RuntimeException("Container Error: Konnte Service oder Klasse '{$id}' nicht auflösen.");
    }

    /**
     * Löst Abhängigkeiten einer Klasse automatisch über PHP Reflection auf.
     */
    private function autowire(string $className): object
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException) {
            throw new \RuntimeException("Container Autowiring Error: Klasse '{$className}' nicht gefunden.");
        }

        if (! $reflectionClass->isInstantiable()) {
            throw new \RuntimeException("Container Autowiring Error: Klasse '{$className}' ist nicht instanziierbar (Interface oder Abstract).");
        }

        $constructor = $reflectionClass->getConstructor();

        // Wenn es keinen Konstruktor gibt, einfach instanziieren
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $parameters   = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Primitiv-Typen (string, int) können nicht geraten werden
            if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();

                    continue;
                }

                throw new \RuntimeException(\sprintf(
                    "Container Autowiring Error: Kann Parameter '$%s' in Klasse '%s' nicht auflösen (Typ fehlt oder ist primitiv).",
                    $parameter->getName(),
                    $className,
                ));
            }

            // Hole die benötigte Instanz rekursiv aus dem Container
            $dependencyClass = $type->getName();
            $dependencies[]  = $this->get($dependencyClass);
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
