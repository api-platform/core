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

namespace ApiPlatform\Meilisearch\State;

use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item provider for Meilisearch.
 *
 * @author API Platform Community
 */
final class ItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly ?DenormalizerInterface $denormalizer = null,
        private readonly ?InflectorInterface $inflector = new Inflector(),
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $resourceClass = $operation->getClass();
        $options = $operation->getStateOptions();
        if (!$options instanceof Options) {
            $options = new Options(index: $this->getIndex($operation));
        }

        $index = $options->getIndex() ?? $this->getIndex($operation);
        $id = (string) reset($uriVariables);

        try {
            $document = $this->client->index($index)->getDocument($id);
        } catch (ApiException $e) {
            if (404 === $e->httpStatus) {
                return null;
            }

            throw new Error(status: $e->httpStatus, detail: $e->getMessage(), title: $e->getMessage(), originalTrace: $e->getTrace());
        }

        $item = $this->denormalizer->denormalize($document, $resourceClass, 'array', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }

    private function getIndex(Operation $operation): string
    {
        return $this->inflector->tableize($operation->getShortName());
    }
}
