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

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonld';

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceClassResolverInterface $resourceClassResolver, private readonly UrlGeneratorInterface $urlGenerator, private readonly ?NameConverterInterface $nameConverter = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            $resourceMetadata = $resourceMetadataCollection[0];
            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = $resourceMetadata->getTypes()[0] ?? "#$shortName";
            $this->populateEntrypointProperties($resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties, $resourceMetadataCollection);
            $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context, $resourceMetadataCollection);
        }

        return $this->computeDoc($object, $this->getClasses($entrypointProperties, $classes));
    }

    /**
     * Populates entrypoint properties.
     */
    private function populateEntrypointProperties(ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array &$entrypointProperties, ?ResourceMetadataCollection $resourceMetadataCollection = null): void
    {
        $hydraCollectionOperations = $this->getHydraOperations(true, $resourceMetadataCollection);
        if (empty($hydraCollectionOperations)) {
            return;
        }

        $entrypointProperty = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => [
                '@id' => sprintf('#Entrypoint/%s', lcfirst($shortName)),
                '@type' => 'hydra:Link',
                'domain' => '#Entrypoint',
                'rdfs:label' => "The collection of $shortName resources",
                'rdfs:range' => [
                    ['@id' => 'hydra:Collection'],
                    [
                        'owl:equivalentClass' => [
                            'owl:onProperty' => ['@id' => 'hydra:member'],
                            'owl:allValuesFrom' => ['@id' => $prefixedShortName],
                        ],
                    ],
                ],
                'hydra:supportedOperation' => $hydraCollectionOperations,
            ],
            'hydra:title' => "The collection of $shortName resources",
            'hydra:readable' => true,
            'hydra:writeable' => false,
        ];

        if ($resourceMetadata->getDeprecationReason()) {
            $entrypointProperty['owl:deprecated'] = true;
        }

        $entrypointProperties[] = $entrypointProperty;
    }

    /**
     * Gets a Hydra class.
     */
    private function getClass(string $resourceClass, ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array $context, ?ResourceMetadataCollection $resourceMetadataCollection = null): array
    {
        $description = $resourceMetadata->getDescription();
        $isDeprecated = $resourceMetadata->getDeprecationReason();

        $class = [
            '@id' => $prefixedShortName,
            '@type' => 'hydra:Class',
            'rdfs:label' => $shortName,
            'hydra:title' => $shortName,
            'hydra:supportedProperty' => $this->getHydraProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context),
            'hydra:supportedOperation' => $this->getHydraOperations(false, $resourceMetadataCollection),
        ];

        if (null !== $description) {
            $class['hydra:description'] = $description;
        }

        if ($isDeprecated) {
            $class['owl:deprecated'] = true;
        }

        return $class;
    }

    /**
     * Creates context for property metatata factories.
     */
    private function getPropertyMetadataFactoryContext(ApiResource $resourceMetadata): array
    {
        $normalizationGroups = $resourceMetadata->getNormalizationContext()[AbstractNormalizer::GROUPS] ?? null;
        $denormalizationGroups = $resourceMetadata->getDenormalizationContext()[AbstractNormalizer::GROUPS] ?? null;
        $propertyContext = [
            'normalization_groups' => $normalizationGroups,
            'denormalization_groups' => $denormalizationGroups,
        ];
        $propertyNameContext = [];

        if ($normalizationGroups) {
            $propertyNameContext['serializer_groups'] = $normalizationGroups;
        }

        if (!$denormalizationGroups) {
            return [$propertyNameContext, $propertyContext];
        }

        if (!isset($propertyNameContext['serializer_groups'])) {
            $propertyNameContext['serializer_groups'] = $denormalizationGroups;

            return [$propertyNameContext, $propertyContext];
        }

        foreach ($denormalizationGroups as $group) {
            $propertyNameContext['serializer_groups'][] = $group;
        }

        return [$propertyNameContext, $propertyContext];
    }

    /**
     * Gets Hydra properties.
     */
    private function getHydraProperties(string $resourceClass, ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array $context): array
    {
        $classes = [];

        $classes[$resourceClass] = true;
        foreach ($resourceMetadata->getOperations() as $operation) {
            /** @var Operation $operation */
            if (!$operation instanceof CollectionOperationInterface) {
                continue;
            }

            $inputMetadata = $operation->getInput();
            if (null !== $inputClass = $inputMetadata['class'] ?? null) {
                $classes[$inputClass] = true;
            }

            $outputMetadata = $operation->getOutput();
            if (null !== $outputClass = $outputMetadata['class'] ?? null) {
                $classes[$outputClass] = true;
            }
        }

        /** @var string[] $classes */
        $classes = array_keys($classes);
        $properties = [];
        [$propertyNameContext, $propertyContext] = $this->getPropertyMetadataFactoryContext($resourceMetadata);

        foreach ($classes as $class) {
            foreach ($this->propertyNameCollectionFactory->create($class, $propertyNameContext) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($class, $propertyName, $propertyContext);

                if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                    continue;
                }

                if ($this->nameConverter) {
                    $propertyName = $this->nameConverter->normalize($propertyName, $class, self::FORMAT, $context);
                }

                $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName);
            }
        }

        return $properties;
    }

    /**
     * Gets Hydra operations.
     */
    private function getHydraOperations(bool $collection, ?ResourceMetadataCollection $resourceMetadataCollection = null): array
    {
        $hydraOperations = [];
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                if ((HttpOperation::METHOD_POST === $operation->getMethod() || $operation instanceof CollectionOperationInterface) !== $collection) {
                    continue;
                }

                $hydraOperations[] = $this->getHydraOperation($operation, $operation->getTypes()[0] ?? "#{$operation->getShortName()}");
            }
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     */
    private function getHydraOperation(HttpOperation $operation, string $prefixedShortName): array
    {
        $method = $operation->getMethod() ?: HttpOperation::METHOD_GET;

        $hydraOperation = $operation->getHydraContext() ?? [];
        if ($operation->getDeprecationReason()) {
            $hydraOperation['owl:deprecated'] = true;
        }

        $shortName = $operation->getShortName();
        $inputMetadata = $operation->getInput() ?? [];
        $outputMetadata = $operation->getOutput() ?? [];

        $inputClass = \array_key_exists('class', $inputMetadata) ? $inputMetadata['class'] : false;
        $outputClass = \array_key_exists('class', $outputMetadata) ? $outputMetadata['class'] : false;

        if ('GET' === $method && $operation instanceof CollectionOperationInterface) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => "Retrieves the collection of $shortName resources.",
                'returns' => null === $outputClass ? 'owl:Nothing' : 'hydra:Collection',
            ];
        } elseif ('GET' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PATCH' === $method) {
            $hydraOperation += [
                '@type' => 'hydra:Operation',
                'hydra:title' => "Updates the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('POST' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:CreateAction'],
                'hydra:title' => "Creates a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PUT' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:ReplaceAction'],
                'hydra:title' => "Replaces the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('DELETE' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:DeleteAction'],
                'hydra:title' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ];
        }

        $hydraOperation['hydra:method'] ?? $hydraOperation['hydra:method'] = $method;

        if (!isset($hydraOperation['rdfs:label']) && isset($hydraOperation['hydra:title'])) {
            $hydraOperation['rdfs:label'] = $hydraOperation['hydra:title'];
        }

        ksort($hydraOperation);

        return $hydraOperation;
    }

    /**
     * Gets the range of the property.
     */
    private function getRange(ApiProperty $propertyMetadata): ?string
    {
        $jsonldContext = $propertyMetadata->getJsonldContext();

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        // TODO: 3.0 support multiple types, default value of types will be [] instead of null
        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;
        if (null === $type) {
            return null;
        }

        if ($type->isCollection() && null !== $collectionType = $type->getCollectionValueTypes()[0] ?? null) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return 'xmls:string';
            case Type::BUILTIN_TYPE_INT:
                return 'xmls:integer';
            case Type::BUILTIN_TYPE_FLOAT:
                return 'xmls:decimal';
            case Type::BUILTIN_TYPE_BOOL:
                return 'xmls:boolean';
            case Type::BUILTIN_TYPE_OBJECT:
                if (null === $className = $type->getClassName()) {
                    return null;
                }

                if (is_a($className, \DateTimeInterface::class, true)) {
                    return 'xmls:dateTime';
                }

                if ($this->resourceClassResolver->isResourceClass($className)) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($className);
                    $operation = $resourceMetadata->getOperation();

                    if (!$operation instanceof HttpOperation) {
                        return "#{$operation->getShortName()}";
                    }

                    return $operation->getTypes()[0] ?? "#{$operation->getShortName()}";
                }
        }

        return null;
    }

    /**
     * Builds the classes array.
     */
    private function getClasses(array $entrypointProperties, array $classes): array
    {
        $classes[] = [
            '@id' => '#Entrypoint',
            '@type' => 'hydra:Class',
            'hydra:title' => 'The API entrypoint',
            'hydra:supportedProperty' => $entrypointProperties,
            'hydra:supportedOperation' => [
                '@type' => 'hydra:Operation',
                'hydra:method' => 'GET',
                'rdfs:label' => 'The API entrypoint.',
                'returns' => '#EntryPoint',
            ],
        ];

        // Constraint violation
        $classes[] = [
            '@id' => '#ConstraintViolation',
            '@type' => 'hydra:Class',
            'hydra:title' => 'A constraint violation',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolation/propertyPath',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'propertyPath',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    'hydra:title' => 'propertyPath',
                    'hydra:description' => 'The property path of the violation',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolation/message',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'message',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    'hydra:title' => 'message',
                    'hydra:description' => 'The message associated with the violation',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
            ],
        ];

        // Constraint violation list
        $classes[] = [
            '@id' => '#ConstraintViolationList',
            '@type' => 'hydra:Class',
            'subClassOf' => 'hydra:Error',
            'hydra:title' => 'A constraint violation list',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolationList/violations',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'violations',
                        'domain' => '#ConstraintViolationList',
                        'range' => '#ConstraintViolation',
                    ],
                    'hydra:title' => 'violations',
                    'hydra:description' => 'The violations',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
            ],
        ];

        return $classes;
    }

    /**
     * Gets a property definition.
     */
    private function getProperty(ApiProperty $propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName): array
    {
        if ($iri = $propertyMetadata->getIris()) {
            $iri = 1 === (is_countable($iri) ? \count($iri) : 0) ? $iri[0] : $iri;
        }

        if (!isset($iri)) {
            $iri = "#$shortName/$propertyName";
        }

        $propertyData = [
            '@id' => $iri,
            '@type' => false === $propertyMetadata->isReadableLink() ? 'hydra:Link' : 'rdf:Property',
            'rdfs:label' => $propertyName,
            'domain' => $prefixedShortName,
        ];

        // TODO: 3.0 support multiple types, default value of types will be [] instead of null
        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;

        if (null !== $type && !$type->isCollection() && (null !== $className = $type->getClassName()) && $this->resourceClassResolver->isResourceClass($className)) {
            $propertyData['owl:maxCardinality'] = 1;
        }

        $property = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => $propertyData,
            'hydra:title' => $propertyName,
            'hydra:required' => $propertyMetadata->isRequired(),
            'hydra:readable' => $propertyMetadata->isReadable(),
            'hydra:writeable' => $propertyMetadata->isWritable() || $propertyMetadata->isInitializable(),
        ];

        if (null !== $range = $this->getRange($propertyMetadata)) {
            $property['hydra:property']['range'] = $range;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $property['hydra:description'] = $description;
        }

        if ($deprecationReason = $propertyMetadata->getDeprecationReason()) {
            $property['owl:deprecated'] = true;
        }

        return $property;
    }

    /**
     * Computes the documentation.
     */
    private function computeDoc(Documentation $object, array $classes): array
    {
        $doc = ['@context' => $this->getContext(), '@id' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT]), '@type' => 'hydra:ApiDocumentation'];

        if ('' !== $object->getTitle()) {
            $doc['hydra:title'] = $object->getTitle();
        }

        if ('' !== $object->getDescription()) {
            $doc['hydra:description'] = $object->getDescription();
        }

        $doc['hydra:entrypoint'] = $this->urlGenerator->generate('api_entrypoint');
        $doc['hydra:supportedClass'] = $classes;

        return $doc;
    }

    /**
     * Builds the JSON-LD context for the API documentation.
     */
    private function getContext(): array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => ContextBuilderInterface::HYDRA_NS,
            'rdf' => ContextBuilderInterface::RDF_NS,
            'rdfs' => ContextBuilderInterface::RDFS_NS,
            'xmls' => ContextBuilderInterface::XML_NS,
            'owl' => ContextBuilderInterface::OWL_NS,
            'schema' => ContextBuilderInterface::SCHEMA_ORG_NS,
            'domain' => ['@id' => 'rdfs:domain', '@type' => '@id'],
            'range' => ['@id' => 'rdfs:range', '@type' => '@id'],
            'subClassOf' => ['@id' => 'rdfs:subClassOf', '@type' => '@id'],
            'expects' => ['@id' => 'hydra:expects', '@type' => '@id'],
            'returns' => ['@id' => 'hydra:returns', '@type' => '@id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
