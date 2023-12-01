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

namespace ApiPlatform\Laravel;

use Psr\Container\ContainerInterface;

// TODO: template T ServiceLocator<ProviderInterface>
final class ServiceLocator implements ContainerInterface
{
    private array $services = [];

    /**
     * @param array<mixed> $services
     */
    public function __construct(array $services = [])
    {
        foreach ($services as $key => $service) {
            $this->services[\is_string($key) ? $key : $service::class] = $service;
        }
    }

    public function get(string $id)
    {
        return $this->services[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
