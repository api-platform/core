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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Advertises the operations declaring a "potentialAction" as schema.org potential actions.
 *
 * @see https://schema.org/docs/actions.html
 */
final class PotentialActionNormalizer implements NormalizerInterface, NormalizerAwareInterface, SerializerAwareInterface, ResetInterface
{
    public const JSONLD_CONTEXT = [
        '@id' => 'https://schema.org/potentialAction',
        '@context' => ['@vocab' => 'https://schema.org/', 'schema' => 'https://schema.org/'],
    ];

    private const DEFAULT_TYPES = [
        'POST' => 'CreateAction',
        'PUT' => 'ReplaceAction',
        'PATCH' => 'UpdateAction',
        'DELETE' => 'DeleteAction',
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
        private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = $this->decorated->normalize($data, $format, $context);

        $operation = $context['operation'] ?? null;
        if (!\is_array($normalizedData) || !$operation instanceof HttpOperation || 'GET' !== $operation->getMethod() || isset($context['api_sub_level']) || isset($context['api_collection_sub_level'])) {
            return $normalizedData;
        }

        if (null === $resourceClass = $operation->getClass()) {
            return $normalizedData;
        }

        $isCollection = $operation instanceof CollectionOperationInterface;
        $item = $isCollection ? null : $data;
        $actions = [];
        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() ?? [] as $actionOperation) {
                if (!$actionOperation instanceof HttpOperation || !$potentialAction = $actionOperation->getPotentialAction()) {
                    continue;
                }

                if (!$this->isEligible($actionOperation, $operation, $resourceClass, $isCollection) || !$this->isGranted($actionOperation, $item, $context)) {
                    continue;
                }

                $uriVariables = $isCollection ? ($context['uri_variables'] ?? []) : $this->identifiersExtractor->getIdentifiersFromItem($item, $actionOperation);
                $inputClass = $this->getInputClass($actionOperation);
                $target = $this->getTarget($actionOperation, $uriVariables, null !== $inputClass);
                foreach ($this->getActions($actionOperation, $potentialAction, $target, $inputClass, $resourceClass) as $action) {
                    $actions[] = $action;
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

    public function reset(): void
    {
        if ($this->decorated instanceof ResetInterface) {
            $this->decorated->reset();
        }
    }

    private function isEligible(HttpOperation $actionOperation, HttpOperation $operation, string $resourceClass, bool $isCollection): bool
    {
        $uriVariables = $actionOperation->getUriVariables() ?? [];

        if ($isCollection) {
            return array_keys($uriVariables) === array_keys($operation->getUriVariables() ?? []);
        }

        if (!$uriVariables) {
            return false;
        }

        foreach ($uriVariables as $link) {
            if ($link->getFromClass() !== $resourceClass) {
                return false;
            }
        }

        return true;
    }

    private function isGranted(HttpOperation $actionOperation, ?object $item, array $context): bool
    {
        if (null === $this->resourceAccessChecker || null === $security = $actionOperation->getSecurity()) {
            return true;
        }

        return $this->resourceAccessChecker->isGranted($actionOperation->getClass(), $security, ['object' => $item, 'previous_object' => $item, 'request' => $context['request'] ?? null]);
    }

    private function getInputClass(HttpOperation $operation): ?string
    {
        if ('DELETE' === $operation->getMethod()) {
            return null;
        }

        $input = $operation->getInput();

        return null === $input ? $operation->getClass() : ($input['class'] ?? null);
    }

    private function getTarget(HttpOperation $operation, array $uriVariables, bool $hasInput): array
    {
        $url = $this->urlGenerator->generate($operation->getRouteName() ?? $operation->getName(), $uriVariables, $operation->getUrlGenerationStrategy() ?? UrlGeneratorInterface::ABS_PATH);

        $target = ['@type' => 'EntryPoint', 'urlTemplate' => $url, 'httpMethod' => $operation->getMethod()];
        if ($hasInput && $encodingType = $this->getMimeTypes($operation->getInputFormats())) {
            $target['encodingType'] = $encodingType;
        }
        if ($contentType = $this->getMimeTypes($operation->getOutputFormats())) {
            $target['contentType'] = $contentType;
        }

        return $target;
    }

    /**
     * @param array<string, mixed>|bool|string $potentialAction
     *
     * @return list<array<string, mixed>>
     */
    private function getActions(HttpOperation $operation, bool|string|array $potentialAction, array $target, ?string $inputClass, string $resourceClass): array
    {
        if (true === $potentialAction && $inputClass) {
            $mapping = $this->classDiscriminatorResolver?->getMappingForClass($inputClass);

            if ('@type' === $mapping?->getTypeProperty()) {
                $actions = [];
                foreach ($mapping->getTypesMapping() as $type => $class) {
                    $actions[] = ['@type' => $type, 'target' => $target] + $this->getInputAnnotations($class, $operation);
                }

                return $actions;
            }
        }

        $extra = \is_array($potentialAction) ? $potentialAction : [];
        $type = \is_string($potentialAction) ? $potentialAction : ($extra['@type'] ?? self::DEFAULT_TYPES[$operation->getMethod()] ?? 'Action');
        $action = ['@type' => $type, 'target' => $target] + $extra;

        if (null === $inputClass) {
            return [$action];
        }

        $annotations = $this->getInputAnnotations($inputClass, $operation);
        if ($inputClass !== $resourceClass) {
            return [$action + $annotations];
        }

        $property = match ($operation->getMethod()) {
            'POST' => 'result',
            'PUT' => 'object',
            default => null,
        };

        if (null === $property) {
            return [$action];
        }

        $node = [];
        if ($types = $operation->getTypes()) {
            $node['@type'] = reset($types);
        }
        $node += $annotations;

        return [$node ? $action + [$property => $node] : $action];
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
        $seen = [];
        $mimeTypes = [];
        foreach ($formats ?? [] as $formatMimeTypes) {
            foreach ((array) $formatMimeTypes as $mimeType) {
                if (!isset($seen[$mimeType])) {
                    $seen[$mimeType] = true;
                    $mimeTypes[] = $mimeType;
                }
            }
        }

        return match (\count($mimeTypes)) {
            0 => null,
            1 => $mimeTypes[0],
            default => $mimeTypes,
        };
    }
}
