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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonapi';

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
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entrypoint = ['links' => ['self' => $this->urlGenerator->generate('api_entrypoint', [], UrlGeneratorInterface::ABS_URL)]];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            /** @var ResourceMetadata|ResourceMetadataCollection */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($resourceMetadata instanceof ResourceMetadata) {
                if (!$resourceMetadata->getCollectionOperations()) {
                    continue;
                }

                try {
                    $entrypoint['links'][lcfirst($resourceMetadata->getShortName())] = $this->iriConverter->getIriFromResourceClass($resourceClass, UrlGeneratorInterface::ABS_URL);
                } catch (InvalidArgumentException $ex) {
                    // Ignore resources without GET operations
                }
            }

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->getOperations() as $operationName => $operation) {
                    if (!$operation->isCollection() || $operation->getUriVariables()) {
                        continue;
                    }

                    try {
                        $entrypoint['links'][lcfirst($resource->getShortName())] = $this->iriConverter instanceof IriConverterInterface ? $this->iriConverter->getIriFromResourceClass($resourceClass, $operationName, UrlGeneratorInterface::ABS_URL) : $this->iriConverter->getIriFromResourceClass($resourceClass, UrlGeneratorInterface::ABS_URL);
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
    public function supportsNormalization($data, $format = null): bool
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
