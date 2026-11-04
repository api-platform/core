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

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface
{
    public const FORMAT = 'jsonhal';

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly IriConverterInterface $iriConverter, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $entrypoint = ['_links' => ['self' => ['href' => $this->urlGenerator->generate('api_entrypoint')]]];

        $bases = [];

        foreach ($data->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $itemOperation = null;

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if ($operation instanceof Get && [] !== ($uriVariables = $operation->getUriVariables() ?? []) && (null === $itemOperation || \count($uriVariables) < \count($itemOperation->getUriVariables() ?? []))) {
                        $itemOperation = $operation;
                    }

                    if (!$operation instanceof CollectionOperationInterface) {
                        continue;
                    }

                    try {
                        $href = $this->iriConverter->getIriFromResource($operation->getClass(), $operation->getUrlGenerationStrategy() ?? UrlGeneratorInterface::ABS_PATH, $operation);
                        $entrypoint['_links'][lcfirst($operation->getShortName())]['href'] = $href;
                    } catch (InvalidArgumentException) {
                        // Ignore resources without GET operations
                    }
                }
            }

            if (null !== $itemOperation) {
                $this->addItemLink($entrypoint, $itemOperation, $bases);
            }
        }

        return $entrypoint;
    }

    private function addItemLink(array &$entrypoint, Get $operation, array &$bases): void
    {
        $uriTemplate = $operation->getUriTemplate();
        if (null === $uriTemplate) {
            return;
        }

        $strategy = $operation->getUrlGenerationStrategy() ?? UrlGeneratorInterface::ABS_PATH;
        $bases[$strategy] ??= rtrim($this->urlGenerator->generate('api_entrypoint', [], $strategy), '/');

        $entrypoint['_links'][lcfirst($operation->getShortName()).'Item'] = ['href' => $bases[$strategy].($operation->getRoutePrefix() ?? '').$uriTemplate, 'templated' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? [Entrypoint::class => true] : [];
    }
}
