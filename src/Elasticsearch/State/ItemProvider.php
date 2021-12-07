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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemProvider implements ProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $denormalizer;
    private $resourceMetadataCollectionFactory;

    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, DenormalizerInterface $denormalizer, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->denormalizer = $denormalizer;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
            $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
            if (false === $operation->getElasticsearch() || (null !== ($uriVariables = $operation->getUriVariables()) && \count($uriVariables) > 1)) {
                return false;
            }
        } catch (OperationNotFoundException $e) {
            return false;
        }

        try {
            $this->documentMetadataFactory->create($resourceClass);
        } catch (IndexNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);

        try {
            $document = $this->client->get([
                'index' => $documentMetadata->getIndex(),
                'type' => $documentMetadata->getType(),
                'id' => (string) reset($identifiers),
            ]);
        } catch (Missing404Exception $e) {
            return null;
        }

        $item = $this->denormalizer->denormalize($document, $resourceClass, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }
}
