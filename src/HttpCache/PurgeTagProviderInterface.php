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

namespace ApiPlatform\HttpCache;

/**
 * Collects extra HTTP cache tags to invalidate for a given entity.
 */
interface PurgeTagProviderInterface
{
    /**
     * @return iterable<string>
     */
    public function getTagsForResource(object $entity): iterable;
}
