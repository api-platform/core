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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Database\Eloquent\Model;

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
    ) {
    }

    /**
     * @param Model[] $data
     */
    public function handleModelSaved(string $eventName, array $data): void
    {
        foreach ($data as $model) {
            if (!$this->resourceClassResolver->isResourceClass($model::class)) {
                return;
            }

            try {
                $this->tags[] = $this->iriConverter->getIriFromResource($model);
                $this->tags[] = $this->iriConverter->getIriFromResource($model::class, operation: new GetCollection(class: $model::class));
            } catch (InvalidArgumentException|ItemNotFoundException $e) {
                // do nothing
            }
        }
    }

    /**
     * @param Model[] $data
     */
    public function handleModelDeleted(string $eventName, array $data): void
    {
        foreach ($data as $model) {
            if (!$this->resourceClassResolver->isResourceClass($model::class)) {
                return;
            }

            try {
                $this->tags[] = $this->iriConverter->getIriFromResource($model);
                $this->tags[] = $this->iriConverter->getIriFromResource($model::class, operation: new GetCollection(class: $model::class));
            } catch (InvalidArgumentException|ItemNotFoundException $e) {
                // do nothing
            }
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
}
