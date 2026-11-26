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

interface DefinitionProvider
{
    /**
     * @return iterable<Definition>
     */
    public function definitions(): iterable;

    public function generation(): int;

    /**
     * Applies the definition changes and bumps the generation in the same write.
     */
    public function publish(?callable $changes = null): int;
}
