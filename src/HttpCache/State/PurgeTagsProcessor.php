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

namespace ApiPlatform\HttpCache\State;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

final class PurgeTagsProcessor implements ProcessorInterface
{
    /**
     * @param iterable<PurgeTagProviderInterface> $providers
     */
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly PurgerInterface $purger,
        private readonly iterable $providers = [],
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isDelete = $operation instanceof DeleteOperationInterface;
        $previousData = $context['previous_data'] ?? null;
        $resourceForDelete = $isDelete && \is_object($data) ? $data : null;

        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        $tags = [];
        foreach ($this->providers as $provider) {
            if ($isDelete && null !== $resourceForDelete) {
                foreach ($provider->getTagsForDelete($resourceForDelete) as $tag) {
                    $tags[$tag] = $tag;
                }
            } elseif (\is_object($previousData) && \is_object($result)) {
                foreach ($provider->getTagsForUpdate($result, $previousData) as $tag) {
                    $tags[$tag] = $tag;
                }
            } elseif (null === $previousData && \is_object($result)) {
                foreach ($provider->getTagsForInsert($result) as $tag) {
                    $tags[$tag] = $tag;
                }
            }
        }

        if ($tags) {
            $this->purger->purge(array_values($tags));
        }

        return $result;
    }
}
