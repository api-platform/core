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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item data provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $indexMetadataFactory;
    private $denormalizer;

    public function __construct(Client $client, DocumentMetadataFactoryInterface $indexMetadataFactory, DenormalizerInterface $denormalizer)
    {
        $this->client = $client;
        $this->indexMetadataFactory = $indexMetadataFactory;
        $this->denormalizer = $denormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        try {
            $this->indexMetadataFactory->create($resourceClass);
        } catch (IndexNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = [])
    {
        if (\is_array($id)) {
            if (1 !== \count($id)) {
                throw new InvalidArgumentException('Composite identifiers not supported.');
            }

            $id = array_values($id)[0];
        }

        $indexMetadata = $this->indexMetadataFactory->create($resourceClass);

        try {
            $document = $this->client->get([
                'index' => $indexMetadata->getIndex(),
                'type' => $indexMetadata->getType(),
                'id' => (string) $id,
            ]);
        } catch (Missing404Exception $e) {
            return null;
        }

        return $this->denormalizer->denormalize($document, $resourceClass, ItemNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
    }
}
