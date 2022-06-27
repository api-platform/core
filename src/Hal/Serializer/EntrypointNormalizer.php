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

namespace ApiPlatform\Hal\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonhal';

    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataFactory;
    /** @var LegacyIriConverterInterface|IriConverterInterface */
    private $iriConverter;
    private $urlGenerator;

    public function __construct($resourceMetadataFactory, $iriConverter, UrlGeneratorInterface $urlGenerator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->urlGenerator = $urlGenerator;

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $entrypoint = ['_links' => ['self' => ['href' => $this->urlGenerator->generate('api_entrypoint')]]];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            /** @var ResourceMetadata|ResourceMetadataCollection */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($resourceMetadata instanceof ResourceMetadata) {
                if (!$resourceMetadata->getCollectionOperations()) {
                    continue;
                }

                try {
                    $entrypoint['_links'][lcfirst($resourceMetadata->getShortName())]['href'] = $this->iriConverter->getIriFromResourceClass($resourceClass);
                } catch (InvalidArgumentException $ex) {
                    // Ignore resources without GET operations
                }
            }

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->getOperations() as $operationName => $operation) {
                    /** @var Operation $operation */
                    if (!$operation instanceof CollectionOperationInterface) {
                        continue;
                    }

                    try {
                        $href = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromResourceClass($resourceClass) : $this->iriConverter->getIriFromResource($operation->getClass(), UrlGeneratorInterface::ABS_PATH, $operation);
                        $entrypoint['_links'][lcfirst($operation->getShortName())]['href'] = $href;
                    } catch (InvalidArgumentException $ex) {
                        // Ignore resources without GET operations
                    }
                }
            }
        }

        return $entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

class_alias(EntrypointNormalizer::class, \ApiPlatform\Core\Hal\Serializer\EntrypointNormalizer::class);
