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
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDeliveryDay;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DeliveryDay as DeliveryDayDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeliveryDay;

final class DeliveryDayPurgeTagProvider implements PurgeTagProviderInterface
{
    public function getTags(object $resource, Operation $operation, array $context = []): iterable
    {
        $previousResource = $context['previous_data'] ?? null;

        yield from $this->getSupplierCollectionTags($resource);
        if (\is_object($previousResource)) {
            yield from $this->getSupplierCollectionTags($previousResource);
        }

        if ($operation instanceof DeleteOperationInterface && ($resource instanceof DeliveryDay || $resource instanceof DeliveryDayDocument) && null !== $resource->id) {
            yield \sprintf('/delivery_days/%d/history', $resource->id);
        }

        if (MappedDeliveryDay::class === $operation->getClass() && $previousResource instanceof MappedDeliveryDay && $resource instanceof DeliveryDay) {
            yield \sprintf('/mapped_delivery_days/day/%s', $previousResource->day);
            yield \sprintf('/mapped_delivery_days/day/%s', $resource->day);
        }
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
