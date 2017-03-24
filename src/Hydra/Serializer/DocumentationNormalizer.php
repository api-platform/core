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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $urlGenerator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, UrlGeneratorInterface $urlGenerator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = $resourceMetadata->getIri() ?? "#$shortName";

            $this->populateEntrypointProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties);
            $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName);
        }

        return $this->computeDoc($object, $this->getClasses($entrypointProperties, $classes));
    }

    /**
     * Populates entrypoint properties.
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $shortName
     * @param string           $prefixedShortName
     * @param array            $entrypointProperties
     */
    private function populateEntrypointProperties(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName, array &$entrypointProperties)
    {
        $hydraCollectionOperations = $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, true);
        if (empty($hydraCollectionOperations)) {
            return;
        }

        $entrypointProperties[] = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => [
                '@id' => sprintf('#Entrypoint/%s', lcfirst($shortName)),
                '@type' => 'hydra:Link',
                'domain' => '#Entrypoint',
                'rdfs:label' => "The collection of $shortName resources",
                'range' => 'hydra:PagedCollection',
                'hydra:supportedOperation' => $hydraCollectionOperations,
            ],
            'hydra:title' => "The collection of $shortName resources",
            'hydra:readable' => true,
            'hydra:writable' => false,
        ];
    }

    /**
     * Gets a Hydra class.
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $shortName
     * @param string           $prefixedShortName
     *
     * @return array
     */
    private function getClass(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName): array
    {
        $class = [
            '@id' => $prefixedShortName,
            '@type' => 'hydra:Class',
            'rdfs:label' => $shortName,
            'hydra:title' => $shortName,
            'hydra:supportedProperty' => $this->getHydraProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName),
            'hydra:supportedOperation' => $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, false),
        ];

        if (null !== $description = $resourceMetadata->getDescription()) {
            $class['hydra:description'] = $description;
        }

        return $class;
    }

    /**
     * Gets the context for the property name factory.
     *
     * @param ResourceMetadata $resourceMetadata
     *
     * @return array
     */
    private function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array
    {
        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context']['groups'])) {
            $context['serializer_groups'] = $attributes['normalization_context']['groups'];
        }

        if (isset($attributes['denormalization_context']['groups'])) {
            if (isset($context['serializer_groups'])) {
                $context['serializer_groups'] += $attributes['denormalization_context']['groups'];
            } else {
                $context['serializer_groups'] = $attributes['denormalization_context']['groups'];
            }
        }

        return $context;
    }

    /**
     * Gets Hydra properties.
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $shortName
     * @param string           $prefixedShortName
     *
     * @return array
     */
    private function getHydraProperties(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName): array
    {
        $properties = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $this->getPropertyNameCollectionFactoryContext($resourceMetadata)) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                continue;
            }

            $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName);
        }

        return $properties;
    }

    /**
     * Gets Hydra operations.
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $prefixedShortName
     * @param bool             $collection
     *
     * @return array
     */
    private function getHydraOperations(string $resourceClass, ResourceMetadata $resourceMetadata, string $prefixedShortName, bool $collection): array
    {
        if (null === $operations = $collection ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return [];
        }

        $hydraOperations = [];
        foreach ($operations as $operationName => $operation) {
            $hydraOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $operation, $prefixedShortName, $collection);
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string           $operationName
     * @param array            $operation
     * @param string           $prefixedShortName
     * @param bool             $collection
     *
     * @return array
     */
    private function getHydraOperation(string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $operation, string $prefixedShortName, bool $collection): array
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
        }

        $hydraOperation = $operation['hydra_context'] ?? [];
        $shortName = $resourceMetadata->getShortName();

        if ('GET' === $method && $collection) {
            $hydraOperation = [
                'hydra:title' => "Retrieves the collection of $shortName resources.",
                'returns' => 'hydra:PagedCollection',
            ] + $hydraOperation;
        } elseif ('GET' === $method) {
            $hydraOperation = [
                'hydra:title' => "Retrieves $shortName resource.",
                'returns' => $prefixedShortName,
            ] + $hydraOperation;
        } elseif ('POST' === $method) {
            $hydraOperation = [
                '@type' => 'hydra:CreateResourceOperation',
                'hydra:title' => "Creates a $shortName resource.",
                'returns' => $prefixedShortName,
                'expects' => $prefixedShortName,
            ] + $hydraOperation;
        } elseif ('PUT' === $method) {
            $hydraOperation = [
                '@type' => 'hydra:ReplaceResourceOperation',
                'hydra:title' => "Replaces the $shortName resource.",
                'returns' => $prefixedShortName,
                'expects' => $prefixedShortName,
            ] + $hydraOperation;
        } elseif ('DELETE' === $method) {
            $hydraOperation = [
                'hydra:title' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ] + $hydraOperation;
        }

        $hydraOperation['@type'] ?? $hydraOperation['@type'] = 'hydra:Operation';
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
     * @param PropertyMetadata $propertyMetadata
     *
     * @return string|null
     */
    private function getRange(PropertyMetadata $propertyMetadata)
    {
        $jsonldContext = $propertyMetadata->getAttributes()['jsonld_context'] ?? [];

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        if (null === $type = $propertyMetadata->getType()) {
            return;
        }

        if ($type->isCollection() && null !== $collectionType = $type->getCollectionValueType()) {
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
                    return;
                }

                if (is_a($className, \DateTimeInterface::class, true)) {
                    return 'xmls:dateTime';
                }

                if ($this->resourceClassResolver->isResourceClass($className)) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($className);

                    return $resourceMetadata->getIri() ?? "#{$resourceMetadata->getShortName()}";
                }
                break;
        }
    }

    /**
     * Builds the classes array.
     *
     * @param array $entrypointProperties
     * @param array $classes
     *
     * @return array
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
                    'hydra:writable' => false,
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
                    'hydra:writable' => false,
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
                    'hydra:writable' => false,
                ],
            ],
        ];

        return $classes;
    }

    /**
     * Gets a property definition.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param string           $propertyName
     * @param string           $prefixedShortName
     * @param string           $shortName
     *
     * @return array
     */
    private function getProperty(PropertyMetadata $propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName): array
    {
        $property = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => [
                '@id' => $propertyMetadata->getIri() ?? "#$shortName/$propertyName",
                '@type' => $propertyMetadata->isReadableLink() ? 'rdf:Property' : 'hydra:Link',
                'rdfs:label' => $propertyName,
                'domain' => $prefixedShortName,
            ],
            'hydra:title' => $propertyName,
            'hydra:required' => $propertyMetadata->isRequired(),
            'hydra:readable' => $propertyMetadata->isReadable(),
            'hydra:writable' => $propertyMetadata->isWritable(),
        ];

        if (null !== $range = $this->getRange($propertyMetadata)) {
            $property['hydra:property']['range'] = $range;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $property['hydra:description'] = $description;
        }

        return $property;
    }

    /**
     * Computes the documentation.
     *
     * @param Documentation $object
     * @param array         $classes
     *
     * @return array
     */
    private function computeDoc(Documentation $object, array $classes): array
    {
        $doc = ['@context' => $this->getContext(), '@id' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT])];

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
     *
     * @return array
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
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}
