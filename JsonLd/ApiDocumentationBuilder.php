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

            $collectionOperations = [];
            foreach ($resource->getCollectionOperations() as $operation) {
                $supportedOperation = [];

                if ('POST' === $operation['hydra:method']) {
                    $supportedOperation['@type'] = 'hydra:CreateResourceOperation';
                    $supportedOperation['hydra:title'] = sprintf('Creates a %s resource.', $shortName);
                    $supportedOperation['hydra:expects'] = $shortName;
                    $supportedOperation['hydra:returns'] = $shortName;
                } else {
                    $supportedOperation['@type'] = 'hydra:Operation';
                    if ('GET' === $operation['hydra:method']) {
                        $supportedOperation['hydra:title'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                        $supportedOperation['hydra:returns'] = 'hydra:PagedCollection';
                    }
                }

                $collectionOperations[] = $this->getSupportedOperation($supportedOperation, $operation);
            }

            $entrypointProperties[] = [
                '@type' => 'hydra:SupportedProperty',
                'hydra:property' => [
                    '@id' => lcfirst($resource->getBeautifiedName()),
                    '@type' => 'hydra:Link',
                    'rdfs:label' => sprintf('The collection of %s resources', $shortName),
                    'domain' => 'Entrypoint',
                    'range' => $resource->getBeautifiedName(),
                ],
                'hydra:title' => sprintf('The collection of %s resources', $shortName),
                'hydra:readable' => true,
                'hydra:writable' => false,
                'hydra:supportedOperation' => $collectionOperations,
            ];

            $classMetadata = $this->classMetadataFactory->getMetadataFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            );
            $shortName = $resource->getShortName();

            $class = [
                '@id' => $shortName,
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
                if (
                    isset($attribute->getTypes()[0]) &&
                    ($className = $attribute->getTypes()[0]->getClass()) &&
                    $this->resources->getResourceForEntity($className)
                ) {
                    $type = 'Hydra:Link';
                } else {
                    $type = 'rdf:Property';
                }

                $property = [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => sprintf('%s/%s', $shortName, $attributeName),
                        '@type' => $type,
                        'rdfs:label' => $attributeName,
                        'domain' => $shortName,
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
                    $operation['hydra:expects'] = $shortName;
                    $operation['hydra:returns'] = $shortName;
                } elseif ('DELETE' === $itemOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:Operation';
                    $operation['hydra:title'] = sprintf('Deletes the %s resource.', $shortName);
                    $operation['hydra:expects'] = $shortName;
                    $operation['hydra:returns'] = 'owl:Nothing';
                } elseif ('GET' === $itemOperation['hydra:method']) {
                    $operation['@type'] = 'hydra:Operation';
                    $operation['hydra:title'] = sprintf('Retrieves %s resource.', $shortName);
                    $operation['hydra:returns'] = $shortName;
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
            '@id' => 'Entrypoint',
            '@type' => 'hydra:Class',
            'hydra:title' => 'The API entrypoint',
            'hydra:supportedProperty' => $entrypointProperties,
        ];

        // Constraint violation
        $classes[] = [
            '@id' => 'ConstraintViolation',
            '@type' => 'hydra:Class',
            'hydra:title' => 'A constraint violation',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => 'ConstraintViolation/propertyPath',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'propertyPath',
                        'domain' => 'ConstraintViolation',
                        'range' => 'rdf:string',
                    ],
                    'hydra:title' => 'propertyPath',
                    'hydra:description' => 'The property path of the violation',
                    'hydra:readable' => true,
                    'hydra:writable' => false,
                ],
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => 'ConstraintViolation/message',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'message',
                        'domain' => 'ConstraintViolation',
                        'range' => 'rdf:string',
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
            '@id' => 'ConstraintViolationList',
            '@type' => 'hydra:Class',
            'subClassOf' => 'hydra:Error',
            'hydra:title' => 'A constraint violation list',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => 'ConstraintViolationList/violation',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'violation',
                        'domain' => 'ConstraintViolationList',
                        'range' => 'ConstraintViolation',
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
                    return ContextBuilder::XML_NS.'string';

                case 'int':
                    return ContextBuilder::XML_NS.'integer';

                case 'float':
                    return ContextBuilder::XML_NS.'double';

                case 'bool':
                    return ContextBuilder::XML_NS.'boolean';

                case 'object':
                    $class = $type->getClass();

                    if ($class) {
                        if ('DateTime' === $class) {
                            return ContextBuilder::XML_NS.'dateTime';
                        }

                        if ($resource = $this->resources->getResourceForEntity($type->getClass())) {
                            return $resource->getShortName();
                        }
                    }
                break;
            }
        }
    }
}
