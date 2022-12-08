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

use ApiPlatform\HttpCache\TagsInvalidatorInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class InvalidatorSpy implements TagsInvalidatorInterface
{
    private array $tags = [];

    public function invalidate(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getInvalidatedTags(): array
    {
        return $this->tags;
    }

    public function clear(): void
    {
        $this->tags = [];
    }
}
