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
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly IriConverterInterface $iriConverter, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $entrypoint = ['links' => ['self' => $this->urlGenerator->generate('api_entrypoint', [], UrlGeneratorInterface::ABS_URL)]];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if (!$operation instanceof CollectionOperationInterface || ($operation instanceof HttpOperation && $operation->getUriVariables())) {
                        continue;
                    }

                    try {
                        $iri = $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_URL, $operation); // @phpstan-ignore-line phpstan issue as type is CollectionOperationInterface & Operation
                        $entrypoint['links'][lcfirst($resource->getShortName())] = $iri;
                    } catch (InvalidArgumentException) {
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
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
