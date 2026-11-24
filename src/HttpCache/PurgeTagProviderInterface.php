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
 * Collects extra HTTP cache tags to invalidate for a given resource.
 */
interface PurgeTagProviderInterface
{
    /**
     * @return iterable<string>
     */
    public function getTagsForInsert(object $resource): iterable;

    /**
     * @param object $resource         the persisted object, the entity or document when the operation declares stateOptions
     * @param object $previousResource the API resource as read before the request, not the entity when the operation declares stateOptions
     *
     * @return iterable<string>
     */
    public function getTagsForUpdate(object $resource, object $previousResource): iterable;

    /**
     * @return iterable<string>
     */
    public function getTagsForDelete(object $resource): iterable;
}
