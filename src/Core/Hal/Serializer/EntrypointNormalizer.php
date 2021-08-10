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

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
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
    public function normalize($object, $format = null, array $context = [])
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
                    if (!$operation->isCollection()) {
                        continue;
                    }

                    try {
                        $entrypoint['_links'][lcfirst($operation->getShortName())]['href'] = $this->iriConverter->getIriFromResourceClass($resourceClass, $operationName);
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
    public function supportsNormalization($data, $format = null)
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
