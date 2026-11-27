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

namespace ApiPlatform\JsonLd;

use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\SubtypeResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements AnonymousContextBuilderInterface, OperationContextBuilderInterface
{
    use ClassInfoTrait;
    use HydraPrefixTrait;
    use SubtypePropertyScopeTrait;

    public const FORMAT = 'jsonld';
    public const HYDRA_PREFIX = 'hydra:';
    public const HYDRA_CONTEXT_HAS_PREFIX = 'hydra_prefix';

    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly UrlGeneratorInterface $urlGenerator, private readonly ?IriConverterInterface $iriConverter = null, private readonly ?NameConverterInterface $nameConverter = null, private array $defaultContext = [], private readonly ?SubtypeResolverInterface $subtypeResolver = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_URL): array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $context = $this->getBaseContext($referenceType);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resource) {
                $resourceName = lcfirst($resource->getShortName());

                $context[$resourceName] = [
                    '@id' => 'Entrypoint/'.$resourceName,
                    '@type' => '@id',
                ];
            }
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
        /** @var HttpOperation $operation */
        $operation = $resourceMetadataCollection->getOperation(null, false, true);

        // Every operation resolves to this same /contexts/{shortName} URI, so union their groups.
        $sharedGroups = $this->getOperationsGroups($resourceMetadataCollection, $operation->getShortName());

        return $this->buildOperationContext($operation, $resourceClass, $referenceType, $sharedGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUri(string $resourceClass, ?int $referenceType = null): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass)[0];

        return $this->generateContextUri($resourceMetadata->getShortName(), $referenceType ?? $resourceMetadata->getUrlGenerationStrategy());
    }

    /**
     * {@inheritdoc}
     */
    public function getAnonymousResourceContext(object $object, array $context = [], int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $outputClass = $this->getObjectClass($object);
        $operation = $context['operation'] ?? new Get(
            shortName: (new \ReflectionClass($outputClass))->getShortName(),
            normalizationContext: [
                self::HYDRA_CONTEXT_HAS_PREFIX => $context[self::HYDRA_CONTEXT_HAS_PREFIX] ?? $this->defaultContext[self::HYDRA_CONTEXT_HAS_PREFIX] ?? true,
                'groups' => [],
            ],
            denormalizationContext: [
                'groups' => [],
            ]
        );

        $types = $context['types'] ?? null;

        if (\is_array($types) && 1 === \count($types)) {
            $types = $types[0];
        }

        $shortName = $operation->getShortName();

        $jsonLdContext = [];

        if (!($context['has_context'] ?? false)) {
            $jsonLdContext['@context'] = $this->getResourceContextWithShortname(
                $outputClass,
                $referenceType,
                $shortName,
                $operation
            );
        }

        $jsonLdContext['@type'] = $types ?? $shortName;

        if (isset($context['iri'])) {
            $jsonLdContext['@id'] = $context['iri'];
        } elseif (true === ($context['gen_id'] ?? true) && $this->iriConverter && !isset($context['item_uri_template'])) {
            $jsonLdContext['@id'] = $this->iriConverter->getIriFromResource($object);
        }

        return $jsonLdContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUriFromOperation(HttpOperation $operation, ?int $referenceType = null): string
    {
        return $this->generateContextUri($operation->getShortName(), $referenceType ?? $operation->getUrlGenerationStrategy());
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextFromOperation(HttpOperation $operation, string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        return $this->buildOperationContext($operation, $resourceClass, $referenceType);
    }

    private function buildOperationContext(HttpOperation $operation, string $resourceClass, int $referenceType, ?array $additionalGroups = []): array
    {
        if (null === $shortName = $operation->getShortName()) {
            return [];
        }

        $context = $operation->getNormalizationContext();
        if ($context['iri_only'] ?? false) {
            $context = $this->getBaseContext($referenceType);
            $context[$this->getHydraPrefix($context).'member']['@type'] = '@id';

            return $context;
        }

        return $this->getResourceContextWithShortname($resourceClass, $referenceType, $shortName, $operation, $additionalGroups);
    }

    /**
     * Null when an operation declares no normalization groups: it serializes everything, so nothing can be filtered.
     */
    private function getOperationsGroups(ResourceMetadataCollection $resourceMetadataCollection, ?string $shortName): ?array
    {
        $groups = [];
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() ?? [] as $operation) {
                if (!$operation instanceof HttpOperation || $operation->getShortName() !== $shortName) {
                    continue;
                }

                if (null === ($operation->getNormalizationContext()['groups'] ?? null)) {
                    return null;
                }

                foreach ((array) $operation->getNormalizationContext()['groups'] as $group) {
                    $groups[$group] = true;
                }
                foreach ((array) ($operation->getDenormalizationContext()['groups'] ?? []) as $group) {
                    $groups[$group] = true;
                }
            }
        }

        return array_keys($groups);
    }

    private function generateContextUri(?string $shortName, ?int $referenceType): string
    {
        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $shortName], $referenceType ?? UrlGeneratorInterface::ABS_PATH);
    }

    private function getResourceContextWithShortname(string $resourceClass, int $referenceType, string $shortName, ?HttpOperation $operation = null, ?array $additionalGroups = []): array
    {
        $context = $this->getBaseContext($referenceType);

        if ($operation && $jsonldContext = $operation->getJsonldContext()) {
            $context = array_merge($context, $jsonldContext);
        }

        $normalizationGroups = $operation?->getNormalizationContext()['groups'] ?? null;
        $denormalizationGroups = $operation?->getDenormalizationContext()['groups'] ?? null;
        $propertyContext = $operation ? ['normalization_groups' => $normalizationGroups, 'denormalization_groups' => $denormalizationGroups] : ['normalization_groups' => [], 'denormalization_groups' => []];

        // A property in none of these groups is never serialized, so it has no place in the @context.
        if (null === $additionalGroups) {
            $propertyNameCollectionContext = [];
        } else {
            $serializerGroups = array_unique([...(array) $normalizationGroups, ...(array) $denormalizationGroups, ...$additionalGroups]);
            $propertyNameCollectionContext = $serializerGroups ? ['serializer_groups' => $serializerGroups] : [];
        }

        $propertyNames = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $propertyNameCollectionContext) as $propertyName) {
            $propertyNames[$propertyName] = true;
            $this->addPropertyContext($context, $resourceClass, $propertyName, $shortName, $propertyContext);
        }

        if (null === $this->subtypeResolver) {
            return $context;
        }

        $this->addTypePropertyContext($context, $resourceClass, $resourceClass, $shortName);

        [$scopes, $iris, $typeProperties] = $this->getSubtypePropertyScopes($this->subtypeResolver, $this->propertyMetadataFactory, $this->propertyNameCollectionFactory, $resourceClass, array_keys($propertyNames), $propertyNameCollectionContext, $propertyContext);
        $termOwners = [];
        foreach ($scopes as $subtype => $properties) {
            foreach ($properties as $propertyName => [$scope, $propertyMetadata]) {
                $scopeName = $this->subtypeResolver->getTypeName($scope);
                $subtypeContext = [];
                if (isset($typeProperties[$subtype][$propertyName])) {
                    $this->addTypePropertyContext($subtypeContext, $subtype, $scope, $scopeName);
                } else {
                    $this->addPropertyContext($subtypeContext, $subtype, $propertyName, $scopeName, $propertyContext, $iris[$propertyName] ?? null, $propertyMetadata);
                }

                foreach ($subtypeContext as $term => $definition) {
                    if (isset($termOwners[$term]) && $context[$term] !== $definition) {
                        throw new RuntimeException(\sprintf('The subtypes "%s" and "%s" of the resource "%s" define the "%s" property differently in JSON-LD: declare the same "iris" and "jsonldContext" or rename one of the properties.', $termOwners[$term], $subtype, $resourceClass, $term));
                    }

                    $termOwners[$term] ??= $subtype;
                    $context[$term] = $definition;
                }
            }
        }

        return $context;
    }

    private function addTypePropertyContext(array &$context, string $class, string $scope, string $scopeName): void
    {
        if (null === $typeProperty = $this->subtypeResolver?->getMapping($class)?->getTypeProperty()) {
            return;
        }

        $convertedName = $this->nameConverter ? $this->nameConverter->normalize($typeProperty, $scope, self::FORMAT) : $typeProperty;
        $context[$convertedName] ??= \sprintf('%s/%s', $scopeName, $convertedName);
    }

    /**
     * @param string|list<string>|null $iris
     */
    private function addPropertyContext(array &$context, string $resourceClass, string $propertyName, string $shortName, array $propertyContext, string|array|null $iris = null, ?ApiProperty $propertyMetadata = null): void
    {
        $propertyMetadata ??= $this->propertyMetadataFactory->create($resourceClass, $propertyName, $propertyContext);

        if ($propertyMetadata->isIdentifier() && true !== $propertyMetadata->isWritable()) {
            return;
        }

        $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT) : $propertyName;
        $jsonldContext = $propertyMetadata->getJsonldContext() ?? [];

        if ($id = $iris ?? $propertyMetadata->getIris()) {
            $id = \is_array($id) && 1 === \count($id) ? $id[0] : $id;
        }

        if (!$id) {
            $id = \sprintf('%s/%s', $shortName, $convertedName);
        }

        if (false === $propertyMetadata->isReadableLink()) {
            $jsonldContext += [
                '@id' => $id,
                '@type' => '@id',
            ];
        }

        if (empty($jsonldContext)) {
            $context[$convertedName] = $id;
        } else {
            $context[$convertedName] = $jsonldContext + [
                '@id' => $id,
            ];
        }
    }
}
