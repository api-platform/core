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
 * Item data provider for data model mapped resource.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MappedDataModelItemDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $itemDataProvider;
    private $resourceMetadataFactory;
    /** @var DenormalizerInterface&NormalizerInterface */
    private $serializer;

    public function __construct(ItemDataProviderInterface $itemDataProvider, ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $serializer)
    {
        $this->itemDataProvider = $itemDataProvider;
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
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $modelClass = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');

        $item = $this->itemDataProvider->getItem($modelClass, $id, $operationName, $context);

        if (null === $item) {
            return null;
        }

        return $this->serializer->denormalize($this->serializer->normalize($item), $resourceClass, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]);
    }
}
