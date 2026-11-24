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
        $tags = [];
        $collectBefore = $operation instanceof DeleteOperationInterface && \is_object($data);

        if ($collectBefore) {
            $tags = $this->collect($data, $operation, $context);
        }

        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (!$collectBefore && \is_object($result)) {
            $tags = $this->collect($result, $operation, $context);
        }

        if ($tags) {
            $this->purger->purge(array_values($tags));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, string>
     */
    private function collect(object $resource, Operation $operation, array $context): array
    {
        $tags = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getTags($resource, $operation, $context) as $tag) {
                $tags[$tag] = $tag;
            }
        }

        return $tags;
    }
}
