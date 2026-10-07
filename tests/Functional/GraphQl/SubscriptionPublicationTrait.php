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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Metadata\GraphQl\Subscription;

/** Exercises preparation and successful acknowledgement for the resource's operations. */
trait SubscriptionPublicationTrait
{
    private function publishSubscriptions(object $object, string $type = 'update'): array
    {
        $container = self::getContainer();
        $manager = $container->get('api_platform.graphql.subscription.subscription_manager');
        $metadata = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        $resourceClass = 'delete' === $type ? $object->resourceClass : $object::class;
        $publications = [];
        foreach ($metadata->create($resourceClass) as $resource) {
            foreach ($resource->getGraphQlOperations() ?? [] as $operation) {
                if (!$operation instanceof Subscription) {
                    continue;
                }
                $publications[] = ['object' => $object, 'operation' => $operation];
            }
        }

        $payloads = [];
        foreach ($manager->getUpdates($publications, $type) as [, $update]) {
            $payloads[] = [$update->getId(), $update->data];
            $manager->acknowledge($update);
        }

        return $payloads;
    }
}
