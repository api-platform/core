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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Advertises the operations flagged with the "potential_action" extra property as schema.org potential actions.
 *
 * @see https://schema.org/docs/actions.html
 */
final class PotentialActionNormalizer implements NormalizerInterface, NormalizerAwareInterface, SerializerAwareInterface
{
    public const EXTRA_PROPERTY = 'potential_action';

    public const JSONLD_CONTEXT = [
        '@id' => 'https://schema.org/potentialAction',
        '@context' => ['@vocab' => 'https://schema.org/', 'schema' => 'https://schema.org/'],
    ];

    private const DEFAULT_TYPES = [
        'POST' => 'schema:CreateAction',
        'PUT' => 'schema:ReplaceAction',
        'PATCH' => 'schema:UpdateAction',
        'DELETE' => 'schema:DeleteAction',
    ];

    public function __construct(
        private readonly NormalizerInterface $decorated,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly IdentifiersExtractorInterface $identifiersExtractor,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = $this->decorated->normalize($data, $format, $context);

        $operation = $context['operation'] ?? null;
        if (!\is_array($normalizedData) || !$operation instanceof HttpOperation || 'GET' !== $operation->getMethod() || null === $operation->getClass() || isset($context['api_sub_level']) || isset($context['api_collection_sub_level'])) {
            return $normalizedData;
        }

        $isCollection = is_iterable($data);
        $actions = [];
        foreach ($this->resourceMetadataCollectionFactory->create($operation->getClass()) as $resource) {
            foreach ($resource->getOperations() ?? [] as $actionOperation) {
                if (!$actionOperation instanceof HttpOperation || true !== ($actionOperation->getExtraProperties()[self::EXTRA_PROPERTY] ?? false) || $isCollection === (bool) $actionOperation->getUriVariables()) {
                    continue;
                }

                if (null !== $target = $this->getTarget($isCollection ? null : $data, $actionOperation)) {
                    array_push($actions, ...$this->getActions($actionOperation, $target));
                }
            }
        }

        if ($actions) {
            $normalizedData['potentialAction'] = $actions;
        }

        return $normalizedData;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        if ($this->decorated instanceof NormalizerAwareInterface) {
            $this->decorated->setNormalizer($normalizer);
        }
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    private function getTarget(?object $item, HttpOperation $operation): ?array
    {
        try {
            $identifiers = $item ? $this->identifiersExtractor->getIdentifiersFromItem($item, $operation) : [];
            $url = $this->urlGenerator->generate($operation->getRouteName() ?? $operation->getName(), $identifiers, $operation->getUrlGenerationStrategy() ?? UrlGeneratorInterface::ABS_PATH);
        } catch (\InvalidArgumentException|\RuntimeException) {
            // the item does not carry the operation's URI variables
            return null;
        }

        $target = ['@type' => 'EntryPoint', 'urlTemplate' => $url, 'httpMethod' => $operation->getMethod()];
        if ($encodingType = $this->getMimeTypes($operation->getInputFormats())) {
            $target['encodingType'] = $encodingType;
        }
        if ($contentType = $this->getMimeTypes($operation->getOutputFormats())) {
            $target['contentType'] = $contentType;
        }

        return $target;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getActions(HttpOperation $operation, array $target): array
    {
        $inputClass = $operation->getInput()['class'] ?? $operation->getClass();
        $mapping = $inputClass ? $this->classDiscriminatorResolver?->getMappingForClass($inputClass) : null;

        if ('@type' === $mapping?->getTypeProperty()) {
            $actions = [];
            foreach ($mapping->getTypesMapping() as $type => $class) {
                $actions[] = ['@type' => $type, 'target' => $target] + $this->getInputAnnotations($class, $operation);
            }

            return $actions;
        }

        $hydraTypes = array_filter((array) ($operation->getHydraContext()['@type'] ?? []), static fn ($type): bool => \is_string($type) && !str_starts_with($type, 'hydra:'));
        $type = reset($hydraTypes) ?: self::DEFAULT_TYPES[$operation->getMethod()] ?? 'schema:Action';

        return [['@type' => $type, 'target' => $target] + ($inputClass ? $this->getInputAnnotations($inputClass, $operation) : [])];
    }

    /**
     * @return array<string, string>
     */
    private function getInputAnnotations(string $class, HttpOperation $operation): array
    {
        $options = ($groups = $operation->getDenormalizationContext()['groups'] ?? null) ? ['serializer_groups' => (array) $groups] : [];

        $annotations = [];
        foreach ($this->propertyNameCollectionFactory->create($class, $options) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($class, $property, $options);
            if ($propertyMetadata->isRequired() && false !== $propertyMetadata->isWritable()) {
                $annotations[($this->nameConverter?->normalize($property, $class, ItemNormalizer::FORMAT) ?? $property).'-input'] = 'required';
            }
        }

        return $annotations;
    }

    /**
     * @param array<string, string|list<string>>|null $formats
     *
     * @return string|list<string>|null
     */
    private function getMimeTypes(?array $formats): string|array|null
    {
        $mimeTypes = array_values(array_unique(array_merge(...array_map(static fn ($mimeTypes): array => (array) $mimeTypes, array_values($formats ?? [])))));

        return match (\count($mimeTypes)) {
            0 => null,
            1 => $mimeTypes[0],
            default => $mimeTypes,
        };
    }
}
