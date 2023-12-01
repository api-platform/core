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

final class FilterLocator implements ContainerInterface
{
    private $filters = [];

    public function get(string $id)
    {
        return $this->filters[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->filters[$id]);
    }
}
