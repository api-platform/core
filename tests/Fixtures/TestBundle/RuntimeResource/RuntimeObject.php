<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

abstract class RuntimeObject
{
    public ?int $id = null;

    /** @var array<string, mixed> */
    private array $attributes = [];

    public function getResource(): string
    {
        return RuntimeResources::nameOf(static::class) ?? '';
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }
}
