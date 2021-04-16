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

namespace ApiPlatform\Core\DataPersister;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\MappedDataModelNormalizer;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Data persister for data model mapped resource.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MappedDataModelDataPersister implements ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;

    private $dataPersister;
    private $itemDataProvider;
    private $resourceMetadataFactory;
    /** @var DenormalizerInterface&NormalizerInterface */
    private $serializer;
    private $identifiersExtractor;
    private $propertyAccessor;

    public function __construct(DataPersisterInterface $dataPersister, ItemDataProviderInterface $itemDataProvider, ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $serializer, IdentifiersExtractorInterface $identifiersExtractor, PropertyAccessorInterface $propertyAccessor)
    {
        $this->dataPersister = $dataPersister;
        $this->itemDataProvider = $itemDataProvider;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        if (!$serializer instanceof DenormalizerInterface) {
            throw new \LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
        }
        $this->serializer = $serializer;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        if (!\is_object($data)) {
            return false;
        }

        try {
            return null !== $this->resourceMetadataFactory->create($this->getObjectClass($data))->getAttribute('data_model');
        } catch (ResourceClassNotFoundException $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        $resourceClass = $this->getObjectClass($data);
        $modelClass = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');

        $toUpdateModel = $this->getModel($data, $resourceClass, $modelClass, $context);

        $modelData = $this->serializer->denormalize($this->serializer->normalize($data, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]), $modelClass, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $toUpdateModel]);

        $persisted = $this->dataPersister->persist($modelData);

        return $this->serializer->denormalize($this->serializer->normalize($persisted), $resourceClass, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = []): void
    {
        $resourceClass = $this->getObjectClass($data);
        $modelClass = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');

        $model = $this->getModel($data, $resourceClass, $modelClass, $context);

        if (!$model) {
            throw new ItemNotFoundException(sprintf('The data model of "%s" has not been found.', $resourceClass));
        }

        $this->dataPersister->remove($model, $context);
    }

    /**
     * @param object $data
     *
     * @return object|null
     */
    private function getModel($data, string $resourceClass, string $modelClass, array $context)
    {
        $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass);

        $ids = [];
        foreach ($identifiers as $identifier) {
            if (null !== $idValue = $this->propertyAccessor->getValue($data, $identifier)) {
                $ids[$identifier] = $idValue;
            }
        }

        return $this->itemDataProvider->getItem($modelClass, $ids, null, $context);
    }
}
