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

namespace ApiPlatform\Laravel\Eloquent\Listener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Purges responses containing modified models from the proxy cache.
 *
 * Sub-resource collection operations (those whose URI depends on parent
 * `uriVariables` such as `/parents/{parentId}/children`) are not invalidated
 * here: the listener has no parent context available.
 */
final class PurgeHttpCacheListener
{
    /**
     * @var string[]
     */
    private array $tags = [];

    public function __construct(
        private readonly PurgerInterface $purger,
        private readonly IriConverterInterface $iriConverter,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
    ) {
    }

    /**
     * @param Model[] $data
     */
    public function handleModelSaved(string $eventName, array $data): void
    {
        foreach ($data as $model) {
            $this->collectTagsFor($model);
        }
    }

    /**
     * @param Model[] $data
     */
    public function handleModelDeleted(string $eventName, array $data): void
    {
        foreach ($data as $model) {
            $this->collectTagsFor($model);
        }
    }

    /**
     * Purges all collected tags at the end of the request.
     */
    public function postFlush(): void
    {
        if (empty($this->tags)) {
            return;
        }

        $this->purger->purge(array_values(array_unique($this->tags)));
        $this->tags = [];
    }

    private function collectTagsFor(Model $model): void
    {
        if (!$this->resourceClassResolver->isResourceClass($model::class)) {
            return;
        }

        foreach ($this->getItemIris($model) as $iri) {
            $this->tags[] = $iri;
        }

        foreach ($this->getCollectionIris($model) as $iri) {
            $this->tags[] = $iri;
        }
    }

    /**
     * @return iterable<string>
     */
    private function getItemIris(Model $model): iterable
    {
        if (!$this->resourceMetadataCollectionFactory) {
            try {
                yield $this->iriConverter->getIriFromResource($model);
            } catch (InvalidArgumentException|ItemNotFoundException|OperationNotFoundException) {
            }

            return;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($model::class) as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                if (!$operation instanceof Get) {
                    continue;
                }
                try {
                    yield $this->iriConverter->getIriFromResource($model, UrlGeneratorInterface::ABS_PATH, $operation);
                } catch (InvalidArgumentException|ItemNotFoundException|OperationNotFoundException) {
                }
            }
        }
    }

    /**
     * @return iterable<string>
     */
    private function getCollectionIris(Model $model): iterable
    {
        if (!$this->resourceMetadataCollectionFactory) {
            try {
                yield $this->iriConverter->getIriFromResource($model::class, operation: new GetCollection(class: $model::class));
            } catch (InvalidArgumentException|ItemNotFoundException|OperationNotFoundException) {
            }

            return;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($model::class) as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                if (!$operation instanceof GetCollection) {
                    continue;
                }
                try {
                    yield $this->iriConverter->getIriFromResource($model::class, UrlGeneratorInterface::ABS_PATH, $operation);
                } catch (InvalidArgumentException|ItemNotFoundException|OperationNotFoundException) {
                    // Sub-resource collections (needing parent uri_variables) cannot
                    // be resolved here and are intentionally skipped.
                }
            }
        }
    }
}
