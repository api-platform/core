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

use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Metadata\Operation;
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

    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, DenormalizerInterface $denormalizer)
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->denormalizer = $denormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $resourceClass = $operation->getClass();
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);

        try {
            $params = [
                'index' => $documentMetadata->getIndex(),
                'id' => (string) reset($uriVariables),
            ];

            if (ElasticsearchVersion::supportsMappingType()) {
                $params['type'] = $documentMetadata->getType();
            }

            $document = $this->client->get($params);
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
