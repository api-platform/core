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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\MappedDataModelNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Collection data provider for data model mapped resource.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MappedDataModelCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $collectionDataProvider;
    private $resourceMetadataFactory;
    /** @var DenormalizerInterface&NormalizerInterface */
    private $serializer;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $serializer)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        if (!$serializer instanceof DenormalizerInterface) {
            throw new \LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
        }
        $this->serializer = $serializer;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        try {
            return null !== $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');
        } catch (ResourceClassNotFoundException $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $modelClass = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');

        $modelCollection = $this->collectionDataProvider->getCollection($modelClass, $operationName, $context);
        $collection = [];

        foreach ($modelCollection as $model) {
            $collection[] = $this->serializer->denormalize($this->serializer->normalize($model), $resourceClass, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]);
        }

        return $collection;
    }
}
