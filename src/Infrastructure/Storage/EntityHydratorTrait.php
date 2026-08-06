<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

trait EntityHydratorTrait
{
    /**
     * Extrahiert ein Objekt (Entity) automatisch in ein Array für die Datenbank (snake_case).
     */
    protected function extractEntity(object $entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $data       = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isInitialized($entity)) {
                continue;
            }

            $value    = $property->getValue($entity);
            $dbColumn = \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName()) ?? '');

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

        return $data;
    }

    /**
     * Baut aus einem Datenbank-Row (snake_case) vollautomatisch dein Objekt zusammen.
     * @template T of object
     * @param  class-string<T> $className
     * @return T
     */
    protected function hydrateEntity(string $className, array $row): object
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

            // NULL Handling
            if ($rawValue === null) {
                $args[] = null;

                continue;
            }

            // Typen-Transformation
            if ($typeName === \DateTimeImmutable::class || $typeName === \DateTime::class || $typeName === \DateTimeInterface::class) {
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
