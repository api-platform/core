<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

use Dunglas\JsonLdApiBundle\Mapping\AttributeMetadata;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
use Symfony\Component\Routing\RouterInterface;

/**
 * Hydra's ApiDocumentation builder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiDocumentationBuilder
{
    /**
     * @var Resources
     */
    private $resources;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description;

    /**
     * @param Resources            $resources
     * @param RouterInterface      $router
     * @param ClassMetadataFactory $classMetadataFactory
     * @param string               $title
     * @param string               $description
     */
    public function __construct(
        Resources $resources,
        RouterInterface $router,
        ClassMetadataFactory $classMetadataFactory,
        $title,
        $description
    ) {
        $this->resources = $resources;
        $this->router = $router;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Gets the Hydra API documentation.
     *
     * @return array
     */
    public function getApiDocumentation()
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($this->resources as $resource) {
            $shortName = $resource->getShortName();
            $prefixedShortName = sprintf('#%s', $shortName);

            $collectionOperations = [];
            foreach ($resource->getCollectionOperations() as $collectionOperation) {
                $operation = [];

                if ('POST' === $collectionOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:CreateResourceOperation';
                    $operation['rdfs:label'] = sprintf('Creates a %s resource.', $shortName);
                    $operation['hydra:title'] = sprintf('Creates a %s resource.', $shortName);
                    $operation['expects'] = $prefixedShortName;
                    $operation['returns'] = $prefixedShortName;
                } else {
                    $operation['@type'] = 'hydra:Operation';
                    if ('GET' === $collectionOperation['hydra:method']) {
                        $operation['hydra:title'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                        $operation['returns'] = 'hydra:PagedCollection';
                    }
                }

                $operation['rdfs:label'] = $operation['hydra:title'];

                $collectionOperations[] = $this->getSupportedOperation($operation, $collectionOperation);
            }

            $entrypointProperties[] = [
                '@type' => 'hydra:SupportedProperty',
                'hydra:property' => [
                    '@id' => sprintf('#Entrypoint/%s', lcfirst($shortName)),
                    '@type' => 'hydra:Link',
                    'rdfs:label' => sprintf('The collection of %s resources', $shortName),
                    'domain' => '#Entrypoint',
                    'range' => 'hydra:PagedCollection',
                    'hydra:supportedOperation' => $collectionOperations,
                ],
                'hydra:title' => sprintf('The collection of %s resources', $shortName),
                'hydra:readable' => true,
                'hydra:writable' => false,
            ];

            $classMetadata = $this->classMetadataFactory->getMetadataFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            );

            $class = [
                '@id' => $prefixedShortName,
                '@type' => 'hydra:Class',
                'rdfs:label' => $resource->getShortName(),
                'hydra:title' => $resource->getShortName(),
                'hydra:description' => $classMetadata->getDescription(),
            ];

            if ($description = $classMetadata->getDescription()) {
                $class['hydra:description'] = $description;
            }

            $properties = [];
            foreach ($classMetadata->getAttributes() as $attributeName => $attribute) {
                if ($attribute->isLink()) {
                    $type = 'Hydra:Link';
                } else {
                    $type = 'rdf:Property';
                }

                $property = [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => sprintf('#%s/%s', $shortName, $attributeName),
                        '@type' => $type,
                        'rdfs:label' => $attributeName,
                        'domain' => $prefixedShortName,
                    ],
                    'hydra:title' => $attributeName,
                    'hydra:required' => $attribute->isRequired(),
                    'hydra:readable' => $attribute->isReadable(),
                    'hydra:writable' => $attribute->isWritable(),
                ];

                if ($range = $this->getRange($attribute)) {
                    $property['hydra:property']['range'] = $range;
                }

                if ($description = $attribute->getDescription()) {
                    $property['hydra:description'] = $description;
                }

                $properties[] = $property;
            }
            $class['hydra:supportedProperty'] = $properties;

            $operations = [];
            foreach ($resource->getItemOperations() as $itemOperation) {
                $operation = [];

                if ('PUT' === $itemOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:ReplaceResourceOperation';
                    $operation['hydra:title'] = sprintf('Replaces the %s resource.', $shortName);
                    $operation['expects'] = $prefixedShortName;
                    $operation['returns'] = $prefixedShortName;
                } elseif ('DELETE' === $itemOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:Operation';
                    $operation['hydra:title'] = sprintf('Deletes the %s resource.', $shortName);
                    $operation['returns'] = 'owl:Nothing';
                } elseif ('GET' === $itemOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:Operation';
                    $operation['hydra:title'] = sprintf('Retrieves %s resource.', $shortName);
                    $operation['returns'] = $prefixedShortName;
                }

                $operation['rdfs:label'] = $operation['hydra:title'];

                $operations[] = $this->getSupportedOperation(
                    $operation,
                    $itemOperation
                );
            }

            $class['hydra:supportedOperation'] = $operations;
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
                'method' => 'GET',
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
            '@context' => $this->router->generate('json_ld_api_context', ['shortName' => 'ApiDocumentation']),
            '@id' => $this->router->generate('json_ld_api_vocab'),
            'hydra:title' => $this->title,
            'hydra:description' => $this->description,
            'hydra:entrypoint' => $this->router->generate('json_ld_api_entrypoint'),
            'hydra:supportedClass' => $classes,
        ];
    }

    /**
     * Copies data from $operation to $supportedOperation except when the key start with "!".
     *
     * @param array $supportedOperation
     * @param array $operation
     *
     * @return array
     */
    private function getSupportedOperation(array $supportedOperation, array $operation)
    {
        foreach ($operation as $key => $value) {
            if (isset($key[0]) && '!' !== $key[0]) {
                $supportedOperation[$key] = $value;
            }
        }

        return $supportedOperation;
    }

    /**
     * Gets the range of the property.
     *
     * @param AttributeMetadata $attributeMetadata
     *
     * @return string|null
     */
    private function getRange(AttributeMetadata $attributeMetadata)
    {
        if (isset($attributeMetadata->getTypes()[0])) {
            $type = $attributeMetadata->getTypes()[0];

            switch ($type->getType()) {
                case 'string':
                    return 'xmls:string';

                case 'int':
                    return 'xmls:integer';

                case 'float':
                    return 'xmls:double';

                case 'bool':
                    return 'xmls:boolean';

                case 'object':
                    $class = $type->getClass();

                    if ($class) {
                        if ('DateTime' === $class) {
                            return 'xmls:dateTime';
                        }

                        if ($resource = $this->resources->getResourceForEntity($type->getClass())) {
                            return sprintf('#%s', $resource->getShortName());
                        }
                    }
                break;
            }
        }
    }
}
