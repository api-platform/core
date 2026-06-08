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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\HttpCache\PurgeTagProviderInterface;

class MockPurgeTagProvider implements PurgeTagProviderInterface
{
    public function getTagsForInsert(object $resource): iterable
    {
        return ['provider_insert'];
    }

    public function getTagsForUpdate(object $resource, object $previousResource): iterable
    {
        return ['provider_update'];
    }

    public function getTagsForDelete(object $resource): iterable
    {
        return ['provider_delete'];
    }
}
