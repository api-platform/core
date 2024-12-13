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

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use const ApiPlatform\JsonLd\HYDRA_CONTEXT;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    use HydraPrefixTrait;
    public const FORMAT = 'jsonld';

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?NameConverterInterface $nameConverter = null,
        private readonly ?array $defaultContext = [],
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $classes = [];
        $entrypointProperties = [];
        $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            $resourceMetadata = $resourceMetadataCollection[0];
            if ($resourceMetadata instanceof ErrorResource && ValidationException::class === $resourceMetadata->getClass()) {
                continue;
            }

            $shortName = $resourceMetadata->getShortName();

            $prefixedShortName = $resourceMetadata->getTypes()[0] ?? "#$shortName";
            $this->populateEntrypointProperties($resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties, $hydraPrefix, $resourceMetadataCollection);
            $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context, $hydraPrefix, $resourceMetadataCollection);
        }

        return $this->computeDoc($object, $this->getClasses($entrypointProperties, $classes, $hydraPrefix), $hydraPrefix);
    }

    /**
     * Populates entrypoint properties.
     */
    private function populateEntrypointProperties(ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array &$entrypointProperties, string $hydraPrefix, ?ResourceMetadataCollection $resourceMetadataCollection = null): void
    {
        $hydraCollectionOperations = $this->getHydraOperations(true, $resourceMetadataCollection, $hydraPrefix);
        if (empty($hydraCollectionOperations)) {
            return;
        }

        $entrypointProperty = [
            '@type' => $hydraPrefix.'SupportedProperty',
            $hydraPrefix.'property' => [
                '@id' => \sprintf('#Entrypoint/%s', lcfirst($shortName)),
                '@type' => $hydraPrefix.'Link',
                'domain' => '#Entrypoint',
                'rdfs:label' => "The collection of $shortName resources",
                'rdfs:range' => [
                    ['@id' => $hydraPrefix.'Collection'],
                    [
                        'owl:equivalentClass' => [
                            'owl:onProperty' => ['@id' => $hydraPrefix.'member'],
                            'owl:allValuesFrom' => ['@id' => $prefixedShortName],
                        ],
                    ],
                ],
                $hydraPrefix.'supportedOperation' => $hydraCollectionOperations,
            ],
            $hydraPrefix.'title' => "The collection of $shortName resources",
            $hydraPrefix.'readable' => true,
            $hydraPrefix.'writeable' => false,
        ];

        if ($resourceMetadata->getDeprecationReason()) {
            $entrypointProperty['owl:deprecated'] = true;
        }

        $entrypointProperties[] = $entrypointProperty;
    }

    /**
     * Gets a Hydra class.
     */
    private function getClass(string $resourceClass, ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array $context, string $hydraPrefix, ?ResourceMetadataCollection $resourceMetadataCollection = null): array
    {
        $description = $resourceMetadata->getDescription();
        $isDeprecated = $resourceMetadata->getDeprecationReason();

        $class = [
            '@id' => $prefixedShortName,
            '@type' => $hydraPrefix.'Class',
            'rdfs:label' => $shortName,
            $hydraPrefix.'title' => $shortName,
            $hydraPrefix.'supportedProperty' => $this->getHydraProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context, $hydraPrefix),
            $hydraPrefix.'supportedOperation' => $this->getHydraOperations(false, $resourceMetadataCollection, $hydraPrefix),
        ];

        if (null !== $description) {
            $class[$hydraPrefix.'description'] = $description;
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
    private function getHydraProperties(string $resourceClass, ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array $context, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
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

                $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName, $hydraPrefix);
            }
        }

        return $properties;
    }

    /**
     * Gets Hydra operations.
     */
    private function getHydraOperations(bool $collection, ?ResourceMetadataCollection $resourceMetadataCollection = null, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $hydraOperations = [];
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                if (('POST' === $operation->getMethod() || $operation instanceof CollectionOperationInterface) !== $collection) {
                    continue;
                }
                $hydraOperations[] = $this->getHydraOperation($operation, $operation->getShortName(), $hydraPrefix);
            }
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     */
    private function getHydraOperation(HttpOperation $operation, string $prefixedShortName, string $hydraPrefix): array
    {
        $method = $operation->getMethod() ?: 'GET';

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
                '@type' => [$hydraPrefix.'Operation', 'schema:FindAction'],
                $hydraPrefix.'title' => "Retrieves the collection of $shortName resources.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $hydraPrefix.'Collection',
            ];
        } elseif ('GET' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:FindAction'],
                $hydraPrefix.'title' => "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PATCH' === $method) {
            $hydraOperation += [
                '@type' => $hydraPrefix.'Operation',
                $hydraPrefix.'title' => "Updates the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('POST' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:CreateAction'],
                $hydraPrefix.'title' => "Creates a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PUT' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:ReplaceAction'],
                $hydraPrefix.'title' => "Replaces the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('DELETE' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:DeleteAction'],
                $hydraPrefix.'title' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ];
        }

        $hydraOperation[$hydraPrefix.'method'] ?? $hydraOperation[$hydraPrefix.'method'] = $method;

        if (!isset($hydraOperation['rdfs:label']) && isset($hydraOperation[$hydraPrefix.'title'])) {
            $hydraOperation['rdfs:label'] = $hydraOperation[$hydraPrefix.'title'];
        }

        ksort($hydraOperation);

        return $hydraOperation;
    }

    /**
     * Gets the range of the property.
     */
    private function getRange(ApiProperty $propertyMetadata): array|string|null
    {
        $jsonldContext = $propertyMetadata->getJsonldContext();

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        $builtInTypes = $propertyMetadata->getBuiltinTypes() ?? [];
        $types = [];

        foreach ($builtInTypes as $type) {
            if ($type->isCollection() && null !== $collectionType = $type->getCollectionValueTypes()[0] ?? null) {
                $type = $collectionType;
            }

            switch ($type->getBuiltinType()) {
                case Type::BUILTIN_TYPE_STRING:
                    if (!\in_array('xmls:string', $types, true)) {
                        $types[] = 'xmls:string';
                    }
                    break;
                case Type::BUILTIN_TYPE_INT:
                    if (!\in_array('xmls:integer', $types, true)) {
                        $types[] = 'xmls:integer';
                    }
                    break;
                case Type::BUILTIN_TYPE_FLOAT:
                    if (!\in_array('xmls:decimal', $types, true)) {
                        $types[] = 'xmls:decimal';
                    }
                    break;
                case Type::BUILTIN_TYPE_BOOL:
                    if (!\in_array('xmls:boolean', $types, true)) {
                        $types[] = 'xmls:boolean';
                    }
                    break;
                case Type::BUILTIN_TYPE_OBJECT:
                    if (null === $className = $type->getClassName()) {
                        continue 2;
                    }

                    if (is_a($className, \DateTimeInterface::class, true)) {
                        if (!\in_array('xmls:dateTime', $types, true)) {
                            $types[] = 'xmls:dateTime';
                        }
                        break;
                    }

                    if ($this->resourceClassResolver->isResourceClass($className)) {
                        $resourceMetadata = $this->resourceMetadataFactory->create($className);
                        $operation = $resourceMetadata->getOperation();

                        if (!$operation instanceof HttpOperation || !$operation->getTypes()) {
                            if (!\in_array("#{$operation->getShortName()}", $types, true)) {
                                $types[] = "#{$operation->getShortName()}";
                            }
                            break;
                        }

                        $types = array_unique(array_merge($types, $operation->getTypes()));
                        break;
                    }
            }
        }

        if ([] === $types) {
            return null;
        }

        return 1 === \count($types) ? $types[0] : $types;
    }

    private function isSingleRelation(ApiProperty $propertyMetadata): bool
    {
        $builtInTypes = $propertyMetadata->getBuiltinTypes() ?? [];

        foreach ($builtInTypes as $type) {
            $className = $type->getClassName();
            if (
                !$type->isCollection()
                && null !== $className
                && $this->resourceClassResolver->isResourceClass($className)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the classes array.
     */
    private function getClasses(array $entrypointProperties, array $classes, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $classes[] = [
            '@id' => '#Entrypoint',
            '@type' => $hydraPrefix.'Class',
            $hydraPrefix.'title' => 'The API entrypoint',
            $hydraPrefix.'supportedProperty' => $entrypointProperties,
            $hydraPrefix.'supportedOperation' => [
                '@type' => $hydraPrefix.'Operation',
                $hydraPrefix.'method' => 'GET',
                'rdfs:label' => 'The API entrypoint.',
                'returns' => 'EntryPoint',
            ],
        ];

        // Constraint violation
        $classes[] = [
            '@id' => '#ConstraintViolation',
            '@type' => $hydraPrefix.'Class',
            $hydraPrefix.'title' => 'A constraint violation',
            $hydraPrefix.'supportedProperty' => [
                [
                    '@type' => $hydraPrefix.'SupportedProperty',
                    $hydraPrefix.'property' => [
                        '@id' => '#ConstraintViolation/propertyPath',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'propertyPath',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    $hydraPrefix.'title' => 'propertyPath',
                    $hydraPrefix.'description' => 'The property path of the violation',
                    $hydraPrefix.'readable' => true,
                    $hydraPrefix.'writeable' => false,
                ],
                [
                    '@type' => $hydraPrefix.'SupportedProperty',
                    $hydraPrefix.'property' => [
                        '@id' => '#ConstraintViolation/message',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'message',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    $hydraPrefix.'title' => 'message',
                    $hydraPrefix.'description' => 'The message associated with the violation',
                    $hydraPrefix.'readable' => true,
                    $hydraPrefix.'writeable' => false,
                ],
            ],
        ];

        // Constraint violation list
        $classes[] = [
            '@id' => '#ConstraintViolationList',
            '@type' => $hydraPrefix.'Class',
            'subClassOf' => $hydraPrefix.'Error',
            $hydraPrefix.'title' => 'A constraint violation list',
            $hydraPrefix.'supportedProperty' => [
                [
                    '@type' => $hydraPrefix.'SupportedProperty',
                    $hydraPrefix.'property' => [
                        '@id' => '#ConstraintViolationList/violations',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'violations',
                        'domain' => '#ConstraintViolationList',
                        'range' => '#ConstraintViolation',
                    ],
                    $hydraPrefix.'title' => 'violations',
                    $hydraPrefix.'description' => 'The violations',
                    $hydraPrefix.'readable' => true,
                    $hydraPrefix.'writeable' => false,
                ],
            ],
        ];

        return $classes;
    }

    /**
     * Gets a property definition.
     */
    private function getProperty(ApiProperty $propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName, string $hydraPrefix): array
    {
        if ($iri = $propertyMetadata->getIris()) {
            $iri = 1 === (is_countable($iri) ? \count($iri) : 0) ? $iri[0] : $iri;
        }

        if (!isset($iri)) {
            $iri = "#$shortName/$propertyName";
        }

        $propertyData = ($propertyMetadata->getJsonldContext()[$hydraPrefix.'property'] ?? []) + [
            '@id' => $iri,
            '@type' => false === $propertyMetadata->isReadableLink() ? $hydraPrefix.'Link' : 'rdf:Property',
            'rdfs:label' => $propertyName,
            'domain' => $prefixedShortName,
        ];

        if (!isset($propertyData['owl:deprecated']) && $propertyMetadata->getDeprecationReason()) {
            $propertyData['owl:deprecated'] = true;
        }

        if (!isset($propertyData['owl:maxCardinality']) && $this->isSingleRelation($propertyMetadata)) {
            $propertyData['owl:maxCardinality'] = 1;
        }

        if (!isset($propertyData['range']) && null !== $range = $this->getRange($propertyMetadata)) {
            $propertyData['range'] = $range;
        }

        $property = [
            '@type' => $hydraPrefix.'SupportedProperty',
            $hydraPrefix.'property' => $propertyData,
            $hydraPrefix.'title' => $propertyName,
            $hydraPrefix.'required' => $propertyMetadata->isRequired(),
            $hydraPrefix.'readable' => $propertyMetadata->isReadable(),
            $hydraPrefix.'writeable' => $propertyMetadata->isWritable() || $propertyMetadata->isInitializable(),
        ];

        if (null !== $description = $propertyMetadata->getDescription()) {
            $property[$hydraPrefix.'description'] = $description;
        }

        return $property;
    }

    /**
     * Computes the documentation.
     */
    private function computeDoc(Documentation $object, array $classes, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $doc = ['@context' => $this->getContext($hydraPrefix), '@id' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT]), '@type' => $hydraPrefix.'ApiDocumentation'];

        if ('' !== $object->getTitle()) {
            $doc[$hydraPrefix.'title'] = $object->getTitle();
        }

        if ('' !== $object->getDescription()) {
            $doc[$hydraPrefix.'description'] = $object->getDescription();
        }

        $doc[$hydraPrefix.'entrypoint'] = $this->urlGenerator->generate('api_entrypoint');
        $doc[$hydraPrefix.'supportedClass'] = $classes;

        return $doc;
    }

    /**
     * Builds the JSON-LD context for the API documentation.
     */
    private function getContext(string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        return [
            HYDRA_CONTEXT,
            [
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
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    public function getSupportedTypes($format): array
    {
        return self::FORMAT === $format ? [Documentation::class => true] : [];
    }
}
