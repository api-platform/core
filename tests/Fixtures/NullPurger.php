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
    private int $purgeCount = 0;

    public function purge(array $iris): void
    {
        $this->iris = $iris;
        ++$this->purgeCount;
    }

    public function getIris(): array
    {
        return $this->iris;
    }

    public function getPurgeCount(): int
    {
        return $this->purgeCount;
    }

    public function clear(): void
    {
        $this->iris = [];
        $this->purgeCount = 0;
    }

    public function getResponseHeaders(array $iris): array
    {
        return ['Cache-Tags' => implode(',', $iris)];
    }
}
