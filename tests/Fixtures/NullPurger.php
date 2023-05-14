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

namespace ApiPlatform\Tests\Fixtures;

use ApiPlatform\HttpCache\PurgerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class NullPurger implements PurgerInterface
{
    private array $iris = [];

    public function purge(array $iris): void
    {
        $this->iris = $iris;
    }

    public function getIris(): array
    {
        return $this->iris;
    }

    public function clear(): void
    {
        $this->iris = [];
    }

    public function getResponseHeaders(array $iris): array
    {
        return ['Cache-Tags' => implode(',', $iris)];
    }
}
