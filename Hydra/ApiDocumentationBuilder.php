<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra;

use Dunglas\ApiBundle\Api\OperationMethodResolverInterface;
use Dunglas\ApiBundle\Api\ResourceClassResolverInterface;
use Dunglas\ApiBundle\Api\UrlGeneratorInterface;
use Dunglas\ApiBundle\JsonLd\ContextBuilderInterface;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadata as ResourceItemMetadata;
use Dunglas\ApiBundle\Metadata\Resource\Factory\CollectionMetadataFactoryInterface as ResourceCollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface as ResourceItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\Factory\CollectionMetadataFactoryInterface as PropertyCollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\Factory\ItemMetadataFactoryInterface as PropertyItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\ItemMetadata as PropertyItemMetadata;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiDocumentationBuilder implements ApiDocumentationBuilderInterface
{
    /**
     * @var ResourceCollectionMetadataFactoryInterface
     */
    private $resourceCollectionMetadataFactory;

    /**
     * @var ResourceItemMetadataFactoryInterface
     */
    private $resourceItemMetadataFactory;

    /**
     * @var PropertyCollectionMetadataFactoryInterface
     */
    private $propertyCollectionMetadataFactory;

    /**
     * @var PropertyItemMetadataFactoryInterface
     */
    private $propertyItemMetadataFactory;

    /**
     * @var ContextBuilderInterface
     */
    private $contextBuilder;

    /**
     * @var ResourceClassResolverInterface
     */
    private $resourceClassResolver;

    /**
     * @var OperationMethodResolverInterface
     */
    private $operationMethodResolver;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    public function __construct(ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory, ResourceItemMetadataFactoryInterface $resourceItemMetadataFactory, PropertyCollectionMetadataFactoryInterface $propertyCollectionMetadataFactory, PropertyItemMetadataFactoryInterface $propertyItemMetadataFactory, ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, UrlGeneratorInterface $urlGenerator, string $title, string $description)
    {
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
        $this->resourceItemMetadataFactory = $resourceItemMetadataFactory;
        $this->propertyCollectionMetadataFactory = $propertyCollectionMetadataFactory;
        $this->propertyItemMetadataFactory = $propertyItemMetadataFactory;
        $this->contextBuilder = $contextBuilder;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->urlGenerator = $urlGenerator;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDocumentation()
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($this->resourceCollectionMetadataFactory->create() as $resourceClass) {
            $resourceItemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);

            $shortName = $resourceItemMetadata->getShortName();
            $prefixedShortName = ($iri = $resourceItemMetadata->getIri()) ? $iri : '#'.$shortName;

            $collectionOperations = [];
            if ($itemOperations = $resourceItemMetadata->getCollectionOperations()) {
                foreach ($itemOperations as $operationName => $collectionOperation) {
                    $collectionOperations[] = $this->getHydraOperation($resourceClass, $resourceItemMetadata, $operationName, $collectionOperation, $prefixedShortName, true);
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

            if ($description = $resourceItemMetadata->getDescription()) {
                $class['hydra:description'] = $description;
            }

            $attributes = $resourceItemMetadata->getAttributes();
            $context = [];
            $properties = [];

            if (isset($attributes['normalization_context']['groups'])) {
                $context['serializer_groups'] = $attributes['normalization_context']['groups'];
            }

            if (isset($attributes['denormalization_context']['groups'])) {
                $context['serializer_groups'] = isset($context['serializer_groups']) ? array_merge($context['serializer_groups'], $attributes['denormalization_context']['groups']) : $context['serializer_groups'];
            }

            foreach ($this->propertyCollectionMetadataFactory->create($resourceClass, $context) as $propertyName) {
                $propertyItemMetadata = $this->propertyItemMetadataFactory->create($resourceClass, $propertyName);

                if ($propertyItemMetadata->isIdentifier() && !$propertyItemMetadata->isWritable()) {
                    continue;
                }

                $type = $propertyItemMetadata->isReadableLink() ? 'rdf:Property' : 'Hydra:Link';
                $property = [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => ($iri = $propertyItemMetadata->getIri()) ? $iri : sprintf('#%s/%s', $shortName, $propertyName),
                        '@type' => $type,
                        'rdfs:label' => $propertyName,
                        'domain' => $prefixedShortName,
                    ],
                    'hydra:title' => $propertyName,
                    'hydra:required' => $propertyItemMetadata->isRequired(),
                    'hydra:readable' => $propertyItemMetadata->isReadable(),
                    'hydra:writable' => $propertyItemMetadata->isWritable(),
                ];

                if ($range = $this->getRange($propertyItemMetadata)) {
                    $property['hydra:property']['range'] = $range;
                }

                if ($description = $propertyItemMetadata->getDescription()) {
                    $property['hydra:description'] = $description;
                }

                $properties[] = $property;
            }
            $class['hydra:supportedProperty'] = $properties;

            $itemOperations = [];

            if ($operations = $resourceItemMetadata->getItemOperations()) {
                foreach ($operations as $operationName => $itemOperation) {
                    $itemOperations[] = $this->getHydraOperation($resourceClass, $resourceItemMetadata, $operationName, $itemOperation, $prefixedShortName, false);
                }
            }

            $class['hydra:supportedOperation'] = $itemOperations;
            $classes[] = $class;
        }

        // Entrypoint
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

        return [
            '@context' => $this->getContext(),
            '@id' => $this->urlGenerator->generate('api_hydra_vocab'),
            'hydra:title' => $this->title,
            'hydra:description' => $this->description,
            'hydra:entrypoint' => $this->urlGenerator->generate('api_jsonld_entrypoint'),
            'hydra:supportedClass' => $classes,
        ];
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     */
    private function getHydraOperation(string $resourceClass, ResourceItemMetadata $resourceItemMetadata, string $operationName, array $operation, string $prefixedShortName, bool $collection) : array
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
        }

        $hydraOperation = $operation['hydra_context'] ?? [];
        $shortName = $resourceItemMetadata->getShortName();

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
     * @param PropertyItemMetadata $propertyItemMetadata
     *
     * @return string|null
     */
    private function getRange(PropertyItemMetadata $propertyItemMetadata)
    {
        $type = $propertyItemMetadata->getType();
        if (!$type) {
            return;
        }

        if ($type->isCollection() && $collectionType = $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case 'string':
                return 'xmls:string';

            case 'int':
                return 'xmls:integer';

            case 'float':
                return 'xmls:double';

            case 'bool':
                return 'xmls:boolean';

            case 'object':
                $className = $type->getClassName();

                if ($className) {
                    if ('DateTime' === $className) {
                        return 'xmls:dateTime';
                    }

                    $className = $type->getClassName();
                    if ($this->resourceClassResolver->isResourceClass($className)) {
                        return sprintf('#%s', $this->resourceItemMetadataFactory->create($className)->getShortName());
                    }
                }
            break;
        }

        return;
    }

    /**
     * Builds the JSON-LD context for the API documentation.
     *
     * @return array
     */
    private function getContext() : array
    {
        return array_merge(
            $this->contextBuilder->getBaseContext(UrlGeneratorInterface::ABS_URL),
            [
                'rdf' => ContextBuilderInterface::RDF_NS,
                'rdfs' => ContextBuilderInterface::RDFS_NS,
                'xmls' => ContextBuilderInterface::XML_NS,
                'owl' => ContextBuilderInterface::OWL_NS,
                'domain' => ['@id' => 'rdfs:domain', '@type' => '@id'],
                'range' => ['@id' => 'rdfs:range', '@type' => '@id'],
                'subClassOf' => ['@id' => 'rdfs:subClassOf', '@type' => '@id'],
                'expects' => ['@id' => 'hydra:expects', '@type' => '@id'],
                'returns' => ['@id' => 'hydra:returns', '@type' => '@id'],
            ]
        );
    }
}
