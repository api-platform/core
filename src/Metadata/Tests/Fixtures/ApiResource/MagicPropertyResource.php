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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

/**
 * Simulates an Eloquent-style model: its identifier is only reachable through
 * a magic accessor, never as a declared PHP property, so `property_exists()`
 * always returns false for it.
 */
class MagicPropertyResource
{
    private array $data = ['uuid' => 'd7e1f6a0-0000-0000-0000-000000000000'];

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
