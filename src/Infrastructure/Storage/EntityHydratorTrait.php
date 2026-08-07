<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

trait EntityHydratorTrait
{
    /**
     * Extrahiert ein Objekt (Entity) automatisch in ein Array für die Datenbank (snake_case).
     *
     * @param array $overrides Manuelle Werte für Spalten, die vom Standard abweichen (z.B. komplexe JSON Arrays).
     */
    protected function extractEntity(object $entity, array $overrides = []): array
    {
        $reflection = new \ReflectionClass($entity);
        $data       = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isInitialized($entity)) {
                continue;
            }

            $propName = $property->getName();
            $dbColumn = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $propName) ?? '');

            // Manuelle Überschreibungen (Overrides) haben Vorrang! (Bezieht sich auf den DB-Spaltennamen)
            if (\array_key_exists($dbColumn, $overrides)) {
                $data[$dbColumn] = $overrides[$dbColumn];

                continue;
            }

            $value = $property->getValue($entity);

            if ($value instanceof \DateTimeInterface) {
                $data[$dbColumn] = $value->format('Y-m-d H:i:s');
            } elseif ($value instanceof \Stringable) {
                $data[$dbColumn] = (string) $value;
            } elseif (\is_bool($value)) {
                $data[$dbColumn] = (int) $value;
            } elseif (\is_array($value)) {
                $data[$dbColumn] = \json_encode($value, \JSON_UNESCAPED_UNICODE);
            } elseif (\is_object($value) && \property_exists($value, 'value')) {
                // Fallback für ValueObjects ohne Stringable Interface
                $data[$dbColumn] = $value->value;
            } else {
                $data[$dbColumn] = $value;
            }
        }

        // Füge Overrides hinzu, die an keinem Property hängen
        foreach ($overrides as $key => $val) {
            if (\array_key_exists($key, $data)) {
                continue;
            }

            $data[$key] = $val;
        }

        return $data;
    }

    /**
     * Baut aus einem Datenbank-Row (snake_case) vollautomatisch dein Objekt zusammen.
     *
     * @param class-string<T> $className
     * @param array           $overrides Werte, die direkt in den Konstruktor gegeben werden sollen (camelCase keys).
     *
     * @return T
     *
     * @template T of object
     */
    protected function hydrateEntity(string $className, array $row, array $overrides = []): object
    {
        $reflection  = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $propName = $parameter->getName();
            // camelCase zu snake_case konvertieren für DB Lookup
            $dbColumn = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $propName) ?? '');

            // Overrides haben höchste Priorität! (Bezieht sich auf den PHP Property-Namen)
            if (\array_key_exists($propName, $overrides)) {
                $args[] = $overrides[$propName];

                continue;
            }

            $type     = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            // Wenn der Wert in der DB nicht existiert und wir einen Default haben
            if (! \array_key_exists($dbColumn, $row)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();

                    continue;
                }
                $args[] = null;

                continue;
            }

            $rawValue = $row[$dbColumn];

            // NULL Handling: Wenn die DB NULL liefert, das Feld aber einen Default-Wert (z.B. []) hat
            // und strikt KEIN null erlaubt, verwenden wir den Default-Wert.
            if ($rawValue === null) {
                if ($type !== null && ! $type->allowsNull() && $parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    $args[] = null;
                }

                continue;
            }

            if (\in_array($typeName, [\DateTimeImmutable::class, \DateTime::class, \DateTimeInterface::class], true)) {
                $args[] = new \DateTimeImmutable($rawValue);
            } elseif ($typeName === 'array') {
                $args[] = \json_decode((string) $rawValue, true) ?? [];
            } elseif ($typeName === 'bool') {
                $args[] = (bool) $rawValue;
            } elseif ($typeName === 'int') {
                $args[] = (int) $rawValue;
            } elseif ($typeName !== null && \class_exists($typeName)) {
                // ValueObjects (wie CharacterId, Username, EmailAddress) instanziieren
                $args[] = new $typeName($rawValue);
            } else {
                $args[] = $rawValue;
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
