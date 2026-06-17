<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

abstract readonly class BaseDTO
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            /** @var static $dto */
            $dto = $reflection->newInstance();

            return $dto;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $data)) {
                $arguments[] = $data[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException("Campo [{$name}] não informado para o DTO " . static::class . '.');
        }

        /** @var static $dto */
        $dto = $reflection->newInstanceArgs($arguments);

        return $dto;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        $reflection = new ReflectionObject($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }
}
