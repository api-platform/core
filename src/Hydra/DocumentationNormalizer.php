<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
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

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $urlGenerator;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, UrlGeneratorInterface $urlGenerator)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
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
            $prefixedShortName = ($iri = $resourceMetadata->getIri()) ? $iri : '#'.$shortName;

            $collectionOperations = [];
            if ($itemOperations = $resourceMetadata->getCollectionOperations()) {
                foreach ($itemOperations as $operationName => $collectionOperation) {
                    $collectionOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $collectionOperation, $prefixedShortName, true);
                }
            }

            if (!empty($collectionOperations)) {
                $entrypointProperties[] = [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => sprintf('#Entrypoint/%s', lcfirst($shortName)),
                        '@type' => 'hydra:Link',
                        'domain' => '#Entrypoint',
                        'rdfs:label' => sprintf('The collection of %s resources', $shortName),
                        'range' => 'hydra:PagedCollection',
                        'hydra:supportedOperation' => $collectionOperations,
                    ],
                    'hydra:title' => sprintf('The collection of %s resources', $shortName),
                    'hydra:readable' => true,
                    'hydra:writable' => false,
                ];
            }

            $class = [
                '@id' => $prefixedShortName,
                '@type' => 'hydra:Class',
                'rdfs:label' => $shortName,
                'hydra:title' => $shortName,
            ];

            if ($description = $resourceMetadata->getDescription()) {
                $class['hydra:description'] = $description;
            }

            $attributes = $resourceMetadata->getAttributes();
            $context = [];
            $properties = [];

            if (isset($attributes['normalization_context']['groups'])) {
                $context['serializer_groups'] = $attributes['normalization_context']['groups'];
            }

            if (isset($attributes['denormalization_context']['groups'])) {
                $context['serializer_groups'] = isset($context['serializer_groups']) ? array_merge($context['serializer_groups'], $attributes['denormalization_context']['groups']) : $context['serializer_groups'];
            }

            foreach ($this->propertyNameCollectionFactory->create($resourceClass, $context) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
                if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                    continue;
                }

                $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName);
            }

            $class['hydra:supportedProperty'] = $properties;

            $itemOperations = [];

            if ($operations = $resourceMetadata->getItemOperations()) {
                foreach ($operations as $operationName => $itemOperation) {
                    $itemOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $itemOperation, $prefixedShortName, false);
                }
            }

            $class['hydra:supportedOperation'] = $itemOperations;
            $classes[] = $class;
        }

        $classes = $this->getClasses($entrypointProperties, $classes);

        return $this->computeDoc($object, $classes);
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     */
    private function getHydraOperation(string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $operation, string $prefixedShortName, bool $collection) : array
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
        }

        $hydraOperation = $operation['hydra_context'] ?? [];
        $shortName = $resourceMetadata->getShortName();

        switch ($method) {
            case 'GET':
                if ($collection) {
                    if (!isset($hydraOperation['hydra:title'])) {
                        $hydraOperation['hydra:title'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                    }

                    if (!isset($hydraOperation['returns'])) {
                        $hydraOperation['returns'] = 'hydra:PagedCollection';
                    }
                } else {
                    if (!isset($hydraOperation['hydra:title'])) {
                        $hydraOperation['hydra:title'] = sprintf('Retrieves %s resource.', $shortName);
                    }
                }
            break;

            case 'POST':
                if (!isset($hydraOperation['@type'])) {
                    $hydraOperation['@type'] = 'hydra:CreateResourceOperation';
                }

                if (!isset($hydraOperation['hydra:title'])) {
                    $hydraOperation['hydra:title'] = sprintf('Creates a %s resource.', $shortName);
                }
            break;

            case 'PUT':
                if (!isset($hydraOperation['@type'])) {
                    $hydraOperation['@type'] = 'hydra:ReplaceResourceOperation';
                }

                if (!isset($hydraOperation['hydra:title'])) {
                    $hydraOperation['hydra:title'] = sprintf('Replaces the %s resource.', $shortName);
                }
                break;

            case 'DELETE':
                if (!isset($hydraOperation['hydra:title'])) {
                    $hydraOperation['hydra:title'] = sprintf('Deletes the %s resource.', $shortName);
                }

                if (!isset($hydraOperation['returns'])) {
                    $hydraOperation['returns'] = 'owl:Nothing';
                }
            break;
        }

        if (!isset($hydraOperation['returns']) &&
            (
                ('GET' === $method && !$collection) ||
                'POST' === $method ||
                'PUT' === $method
            )
        ) {
            $hydraOperation['returns'] = $prefixedShortName;
        }

        if (!isset($hydraOperation['expects']) &&
            ('POST' === $method || 'PUT' === $method)) {
            $hydraOperation['expects'] = $prefixedShortName;
        }

        if (!isset($hydraOperation['@type'])) {
            $hydraOperation['@type'] = 'hydra:Operation';
        }

        if (!isset($hydraOperation['hydra:method'])) {
            $hydraOperation['hydra:method'] = $method;
        }

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
        $type = $propertyMetadata->getType();
        if (!$type) {
            return;
        }

        if ($type->isCollection() && $collectionType = $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return 'xmls:string';

            case Type::BUILTIN_TYPE_INT:
                return 'xmls:integer';

            case Type::BUILTIN_TYPE_FLOAT:
                return 'xmls:number';

            case Type::BUILTIN_TYPE_BOOL:
                return 'xmls:boolean';

            case Type::BUILTIN_TYPE_OBJECT:
                $className = $type->getClassName();

                if (null !== $className) {
                    $reflection = new \ReflectionClass($className);
                    if ($reflection->implementsInterface(\DateTimeInterface::class)) {
                        return 'xmls:dateTime';
                    }

                    $className = $type->getClassName();
                    if ($this->resourceClassResolver->isResourceClass($className)) {
                        return sprintf('#%s', $this->resourceMetadataFactory->create($className)->getShortName());
                    }
                }
           break;
        }
    }

    /*
     * Builds the classes array.
     */
    private function getClasses(array $entrypointProperties, array $classes) : array
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
                        '@id' => '#ConstraintViolationList/violation',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'violation',
                        'domain' => '#ConstraintViolationList',
                        'range' => '#ConstraintViolation',
                    ],
                    'hydra:title' => 'violation',
                    'hydra:description' => 'The violations',
                    'hydra:readable' => true,
                    'hydra:writable' => false,
                ],
            ],
        ];

        return $classes;
    }

    private function getProperty(PropertyMetadata $propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName): array
    {
        $type = $propertyMetadata->isReadableLink() ? 'rdf:Property' : 'Hydra:Link';
        $property = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => [
                '@id' => ($iri = $propertyMetadata->getIri()) ? $iri : sprintf('#%s/%s', $shortName, $propertyName),
                '@type' => $type,
                'rdfs:label' => $propertyName,
                'domain' => $prefixedShortName,
            ],
            'hydra:title' => $propertyName,
            'hydra:required' => $propertyMetadata->isRequired(),
            'hydra:readable' => $propertyMetadata->isReadable(),
            'hydra:writable' => $propertyMetadata->isWritable(),
        ];

        if ($range = $this->getRange($propertyMetadata)) {
            $property['hydra:property']['range'] = $range;
        }

        if ($description = $propertyMetadata->getDescription()) {
            $property['hydra:description'] = $description;
        }

        return $property;
    }

    private function computeDoc($object, array $classes): array
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
    private function getContext() : array
    {
        return
            [
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
