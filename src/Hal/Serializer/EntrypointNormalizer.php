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
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly IriConverterInterface $iriConverter, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $entrypoint = ['_links' => ['self' => ['href' => $this->urlGenerator->generate('api_entrypoint')]]];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    /** @var Operation $operation */
                    if (!$operation instanceof CollectionOperationInterface) {
                        continue;
                    }

                    try {
                        $href = $this->iriConverter->getIriFromResource($operation->getClass(), UrlGeneratorInterface::ABS_PATH, $operation);
                        $entrypoint['_links'][lcfirst($operation->getShortName())]['href'] = $href;
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
