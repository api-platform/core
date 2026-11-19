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

namespace ApiPlatform\Tests\Fixtures\TestBundle\HttpCache;

use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DeliveryDay as DeliveryDayDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeliveryDay;

final class DeliveryDayPurgeTagProvider implements PurgeTagProviderInterface
{
    public function getTagsForInsert(object $resource): iterable
    {
        return $this->getSupplierCollectionTags($resource);
    }

    public function getTagsForUpdate(object $resource, object $previousResource): iterable
    {
        yield from $this->getSupplierCollectionTags($resource);
        yield from $this->getSupplierCollectionTags($previousResource);
    }

    public function getTagsForDelete(object $resource): iterable
    {
        return $this->getSupplierCollectionTags($resource);
    }

    /**
     * @return string[]
     */
    private function getSupplierCollectionTags(object $resource): array
    {
        if (!($resource instanceof DeliveryDay || $resource instanceof DeliveryDayDocument) || null === $resource->supplier?->id) {
            return [];
        }

        return [\sprintf('/suppliers/%d/delivery_days', $resource->supplier->id)];
    }
}
