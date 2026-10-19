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

namespace ApiPlatform\Symfony\Doctrine\EventListener;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Resolves the API resources of persisted objects and collects the HTTP cache tags to purge.
 *
 * @internal
 */
trait PurgeHttpCacheTagsTrait
{
    use ClassInfoTrait;
    private array $tags = [];

    private function gatherResourceAndItemTags(object $entity, bool $purgeItem): void
    {
        $resources = $this->getResourcesForEntity($entity);

        foreach ($resources as $resource) {
            try {
                foreach ($this->getCollectionOperations($resource) as $operation) {
                    $iri = $this->iriConverter->getIriFromResource($resource, UrlGeneratorInterface::ABS_PATH, $operation);
                    $this->tags[$iri] = $iri;
                }

                if ($purgeItem) {
                    $this->addTagForItem($entity);
                }
            } catch (OperationNotFoundException|InvalidArgumentException) {
            }
        }
    }

    /**
     * @return iterable<Operation&CollectionOperationInterface>
     */
    private function getCollectionOperations(object $resource): iterable
    {
        if (!$this->resourceMetadataCollectionFactory) {
            yield new GetCollection();

            return;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($this->getObjectClass($resource)) as $apiResource) {
            foreach ($apiResource->getOperations() ?? [] as $operation) {
                if (!$operation instanceof CollectionOperationInterface || !$operation instanceof HttpOperation) {
                    continue;
                }

                if (!empty($operation->getUriVariables())) {
                    continue;
                }

                yield $operation;
            }
        }
    }

    private function addTagForItem(mixed $value): void
    {
        $resources = $this->getResourcesForEntity($value);

        foreach ($resources as $resource) {
            try {
                $iri = $this->iriConverter->getIriFromResource($resource);
                $this->tags[$iri] = $iri;
            } catch (OperationNotFoundException|InvalidArgumentException) {
            }
        }
    }

    private function getResourcesForEntity(object $entity): array
    {
        $resources = [];

        if (!$this->resourceClassResolver->isResourceClass($class = $this->getObjectClass($entity))) {
            // is the entity mapped to resource(s)?
            if (!$this->objectMapper) {
                return [];
            }

            if ($this->objectMapperMetadata) {
                $mappings = $this->objectMapperMetadata->create($entity);

                if (!$mappings) {
                    return [];
                }

                $resources = array_map(
                    fn ($mapping) => $this->objectMapper->map($entity, $mapping->target),
                    $mappings
                );
            } else {
                $mapAttributes = (new \ReflectionClass($class))->getAttributes(Map::class);

                if (!$mapAttributes) {
                    return [];
                }

                // loop over all mappings to fetch all resources mapped to this entity
                $resources = array_map(
                    fn ($mapAttribute) => $this->objectMapper->map($entity, $mapAttribute->newInstance()->target),
                    $mapAttributes
                );
            }
        } else {
            $resources[] = $entity;
        }

        return $resources;
    }
}
