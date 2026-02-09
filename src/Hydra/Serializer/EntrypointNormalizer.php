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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface
{
    public const FORMAT = 'jsonld';

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly IriConverterInterface $iriConverter,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $entrypoint = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Entrypoint']),
            '@id' => $this->urlGenerator->generate('api_entrypoint'),
            '@type' => 'Entrypoint',
        ];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadata as $resource) {
                $isAlternate = $resource->getExtraProperties()['is_alternate_resource_metadata'] ?? false;

                foreach ($resource->getOperations() as $operation) {
                    $baseKey = lcfirst($resource->getShortName());
                    $key = $baseKey;

                    if (true === $operation->getHideHydraOperation() || !$operation instanceof CollectionOperationInterface) {
                        continue;
                    }

                    // If this is an alternate and the key already exists, generate an indexed key
                    if ($isAlternate && isset($entrypoint[$key])) {
                        // Find the next available index
                        $index = 1;
                        while (isset($entrypoint[$baseKey.'_'.$index])) {
                            ++$index;
                        }
                        $key = $baseKey.'_'.$index;

                        if ($this->logger) {
                            $this->logger->warning('Multiple ApiResource declarations with the same shortName "{shortName}" for class "{class}"; consider using distinct shortNames.', ['shortName' => $resource->getShortName(), 'class' => $resourceClass]);
                        }
                    } elseif (isset($entrypoint[$key])) {
                        continue;
                    }

                    try {
                        $entrypoint[$key] = $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, $operation);
                    } catch (InvalidArgumentException|OperationNotFoundException) {
                        // Ignore resources without GET operations
                    }
                }
            }
        }

        ksort($entrypoint);

        return $entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }

    /**
     * @param string|null $format
     */
    public function getSupportedTypes($format): array
    {
        return self::FORMAT === $format ? [Entrypoint::class => true] : [];
    }
}
