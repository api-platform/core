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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Common\State\ResourceTransformerInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TransformedDummyEntity;
use Doctrine\Persistence\ObjectManager;

class TransformedDummyEntityRessourceTransformer implements ResourceTransformerInterface
{
    public function toResource(object $entityOrDocument): object
    {
        \assert($entityOrDocument instanceof TransformedDummyEntity);

        $resource = new TransformedDummyEntityRessource();
        $resource->id = $entityOrDocument->getId();
        $resource->year = (int) $entityOrDocument->getDate()->format('Y');

        // since patches will call the provider first, we might as well keep a ref to the entity
        $resource->entityRef = $entityOrDocument;

        return $resource;
    }

    public function fromResource(object $resource, ObjectManager $objectManager): object
    {
        \assert($resource instanceof TransformedDummyEntityRessource);

        // since we keep the ref, we can do this
        $entity = $resource->entityRef ?? new TransformedDummyEntity();

        // otherwise we could do that
        $entity = match ($resource->id) {
            null => new TransformedDummyEntity(),
            default => $objectManager->find(TransformedDummyEntity::class, $resource->id) ?? new TransformedDummyEntity(),
        };

        // set the date to 01/01 by default
        $entity->setDate(new \DateTimeImmutable($resource->year.'-01-01'));

        return $entity;
    }
}
