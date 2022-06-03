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
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
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

    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;

    /**
     * @var PropertyMetadataFactoryInterface|LegacyPropertyMetadataFactoryInterface
     */
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $urlGenerator;
    private $subresourceOperationFactory;
    private $nameConverter;

    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver = null, UrlGeneratorInterface $urlGenerator, SubresourceOperationFactoryInterface $subresourceOperationFactory = null, NameConverterInterface $nameConverter = null)
    {
        if ($operationMethodResolver) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0.', OperationMethodResolverInterface::class, __METHOD__), \E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        if ($subresourceOperationFactory) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Using "%s" is deprecated and will be removed.', SubresourceOperationFactoryInterface::class));
        }

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->urlGenerator = $urlGenerator;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            if ($resourceMetadataCollection instanceof ResourceMetadata) {
                $shortName = $resourceMetadataCollection->getShortName();
                $prefixedShortName = $resourceMetadataCollection->getIri() ?? "#$shortName";

                $this->populateEntrypointProperties($resourceClass, $resourceMetadataCollection, $shortName, $prefixedShortName, $entrypointProperties);
                $classes[] = $this->getClass($resourceClass, $resourceMetadataCollection, $shortName, $prefixedShortName, $context);
                continue;
            }

            $resourceMetadata = $resourceMetadataCollection[0];
            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = $resourceMetadata->getTypes()[0] ?? "#$shortName";
            $this->populateEntrypointProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties, $resourceMetadataCollection);
            $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context, $resourceMetadataCollection);
        }

        return $this->computeDoc($object, $this->getClasses($entrypointProperties, $classes));
    }

    /**
     * Populates entrypoint properties.
     *
     * @param ResourceMetadata|ApiResource $resourceMetadata
     */
    private function populateEntrypointProperties(string $resourceClass, $resourceMetadata, string $shortName, string $prefixedShortName, array &$entrypointProperties, ?ResourceMetadataCollection $resourceMetadataCollection = null)
    {
        $hydraCollectionOperations = $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, true, $resourceMetadataCollection);
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

        if ($resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getCollectionOperationAttribute('GET', 'deprecation_reason', null, true) : $resourceMetadata->getDeprecationReason()) {
            $entrypointProperty['owl:deprecated'] = true;
        }

        $entrypointProperties[] = $entrypointProperty;
    }

    /**
     * Gets a Hydra class.
     *
     * @param ResourceMetadata|ApiResource $resourceMetadata
     */
    private function getClass(string $resourceClass, $resourceMetadata, string $shortName, string $prefixedShortName, array $context, ?ResourceMetadataCollection $resourceMetadataCollection = null): array
    {
        if ($resourceMetadata instanceof ApiResource) {
            $description = $resourceMetadata->getDescription();
            $isDeprecated = $resourceMetadata->getDeprecationReason();
        } else {
            $description = $resourceMetadata->getDescription();
            $isDeprecated = $resourceMetadata->getAttribute('deprecation_reason');
        }

        $class = [
            '@id' => $prefixedShortName,
            '@type' => 'hydra:Class',
            'rdfs:label' => $shortName,
            'hydra:title' => $shortName,
            'hydra:supportedProperty' => $this->getHydraProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context),
            'hydra:supportedOperation' => $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, false, $resourceMetadataCollection),
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
     * Gets the context for the property name factory.
     */
    private function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array
    {
        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context'][AbstractNormalizer::GROUPS])) {
            $context['serializer_groups'] = (array) $attributes['normalization_context'][AbstractNormalizer::GROUPS];
        }

        if (!isset($attributes['denormalization_context'][AbstractNormalizer::GROUPS])) {
            return $context;
        }

        if (isset($context['serializer_groups'])) {
            foreach ((array) $attributes['denormalization_context'][AbstractNormalizer::GROUPS] as $groupName) {
                $context['serializer_groups'][] = $groupName;
            }

            return $context;
        }

        $context['serializer_groups'] = (array) $attributes['denormalization_context'][AbstractNormalizer::GROUPS];

        return $context;
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
     *
     * @param ResourceMetadata|ApiResource $resourceMetadata
     */
    private function getHydraProperties(string $resourceClass, $resourceMetadata, string $shortName, string $prefixedShortName, array $context): array
    {
        $classes = [];

        if ($resourceMetadata instanceof ResourceMetadata) {
            foreach ($resourceMetadata->getCollectionOperations() as $operationName => $operation) {
                $inputMetadata = $resourceMetadata->getTypedOperationAttribute(OperationType::COLLECTION, $operationName, 'input', ['class' => $resourceClass], true);
                if (null !== $inputClass = $inputMetadata['class'] ?? null) {
                    $classes[$inputClass] = true;
                }

                $outputMetadata = $resourceMetadata->getTypedOperationAttribute(OperationType::COLLECTION, $operationName, 'output', ['class' => $resourceClass], true);
                if (null !== $outputClass = $outputMetadata['class'] ?? null) {
                    $classes[$outputClass] = true;
                }
            }
        } else {
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
        }

        /** @var string[] $classes */
        $classes = array_keys($classes);
        $properties = [];
        if ($resourceMetadata instanceof ResourceMetadata) {
            $propertyNameContext = $this->getPropertyNameCollectionFactoryContext($resourceMetadata);
            $propertyContext = [];
        } else {
            [$propertyNameContext, $propertyContext] = $this->getPropertyMetadataFactoryContext($resourceMetadata);
        }

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
     *
     * @param ResourceMetadata|ApiResource $resourceMetadata
     */
    private function getHydraOperations(string $resourceClass, $resourceMetadata, string $prefixedShortName, bool $collection, ?ResourceMetadataCollection $resourceMetadataCollection = null): array
    {
        if ($resourceMetadata instanceof ResourceMetadata) {
            if (null === $operations = $collection ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
                return [];
            }

            $hydraOperations = [];
            foreach ($operations as $operationName => $operation) {
                $hydraOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $operation, $prefixedShortName, $collection ? OperationType::COLLECTION : OperationType::ITEM);
            }
        } else {
            $hydraOperations = [];
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ((HttpOperation::METHOD_POST === $operation->getMethod() || $operation instanceof CollectionOperationInterface) !== $collection) {
                        continue;
                    }

                    $hydraOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $operation, $operation->getTypes()[0] ?? "#{$operation->getShortName()}", null);
                }
            }
        }

        if (null !== $this->subresourceOperationFactory && !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $operation) {
                $subresourceMetadata = $this->resourceMetadataFactory->create($operation['resource_class']);
                $propertyMetadata = $this->propertyMetadataFactory->create(end($operation['identifiers'])[0], $operation['property']);
                $hydraOperations[] = $this->getHydraOperation($resourceClass, $subresourceMetadata, $operation['route_name'], $operation, "#{$subresourceMetadata->getShortName()}", OperationType::SUBRESOURCE, $propertyMetadata->getSubresource());
            }
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     *
     * @param ResourceMetadata|ApiResource $resourceMetadata
     * @param SubresourceMetadata          $subresourceMetadata
     * @param array|HttpOperation          $operation
     */
    private function getHydraOperation(string $resourceClass, $resourceMetadata, string $operationName, $operation, string $prefixedShortName, ?string $operationType = null, SubresourceMetadata $subresourceMetadata = null): array
    {
        if ($operation instanceof HttpOperation) {
            $method = $operation->getMethod() ?: HttpOperation::METHOD_GET;
        } elseif ($this->operationMethodResolver) {
            if (OperationType::COLLECTION === $operationType) {
                $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
            } elseif (OperationType::ITEM === $operationType) {
                $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
            } else {
                $method = 'GET';
            }
        } else {
            $method = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method', 'GET');
        }

        $hydraOperation = $operation instanceof HttpOperation ? ($operation->getHydraContext() ?? []) : ($operation['hydra_context'] ?? []);
        if ($operation instanceof HttpOperation ? $operation->getDeprecationReason() : $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) {
            $hydraOperation['owl:deprecated'] = true;
        }

        if ($operation instanceof HttpOperation) {
            $shortName = $operation->getShortName();
            $inputMetadata = $operation->getInput() ?? [];
            $outputMetadata = $operation->getOutput() ?? [];
            $operationType = $operation instanceof CollectionOperationInterface ? OperationType::COLLECTION : OperationType::ITEM;
        } else {
            $shortName = $resourceMetadata->getShortName();
            $inputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'input', ['class' => false]);
            $outputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output', ['class' => false]);
        }

        $inputClass = \array_key_exists('class', $inputMetadata) ? $inputMetadata['class'] : false;
        $outputClass = \array_key_exists('class', $outputMetadata) ? $outputMetadata['class'] : false;

        if ('GET' === $method && OperationType::COLLECTION === $operationType) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => "Retrieves the collection of $shortName resources.",
                'returns' => 'hydra:Collection',
            ];
        } elseif ('GET' === $method && OperationType::SUBRESOURCE === $operationType) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => $subresourceMetadata && $subresourceMetadata->isCollection() ? "Retrieves the collection of $shortName resources." : "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : "#$shortName",
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
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     */
    private function getRange($propertyMetadata): ?string
    {
        $jsonldContext = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getAttributes()['jsonld_context'] ?? [] : $propertyMetadata->getJsonldContext();

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        // TODO: 3.0 support multiple types, default value of types will be [] instead of null
        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : $propertyMetadata->getBuiltinTypes()[0] ?? null;
        if (null === $type) {
            return null;
        }

        if ($type->isCollection() && null !== $collectionType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) {
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
                    if ($resourceMetadata instanceof ResourceMetadataCollection) {
                        $operation = $resourceMetadata->getOperation();

                        if (!$operation instanceof HttpOperation) {
                            return "#{$operation->getShortName()}";
                        }

                        return $operation->getTypes()[0] ?? "#{$operation->getShortName()}";
                    }

                    return $resourceMetadata->getIri() ?? "#{$resourceMetadata->getShortName()}";
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
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     */
    private function getProperty($propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName): array
    {
        if ($propertyMetadata instanceof PropertyMetadata) {
            $iri = $propertyMetadata->getIri();
        } else {
            if ($iri = $propertyMetadata->getIris()) {
                $iri = 1 === \count($iri) ? $iri[0] : $iri;
            }
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
        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : $propertyMetadata->getBuiltinTypes()[0] ?? null;

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

        if ($deprecationReason = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getAttribute('deprecation_reason') : $propertyMetadata->getDeprecationReason()) {
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
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

class_alias(DocumentationNormalizer::class, \ApiPlatform\Core\Hydra\Serializer\DocumentationNormalizer::class);
