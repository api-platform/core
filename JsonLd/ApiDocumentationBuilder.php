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
     * @var ContextBuilder
     */
    private $contextBuilder;
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
     * @param ContextBuilder       $contextBuilder
     * @param RouterInterface      $router
     * @param ClassMetadataFactory $classMetadataFactory
     * @param string               $title
     * @param string               $description
     */
    public function __construct(
        Resources $resources,
        ContextBuilder $contextBuilder,
        RouterInterface $router,
        ClassMetadataFactory $classMetadataFactory,
        $title,
        $description
    ) {
        $this->resources = $resources;
        $this->contextBuilder = $contextBuilder;
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
                $collectionOperations[] = $this->getOperation($collectionOperation);
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
                $operations[] = $this->getOperation($itemOperation);
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
            '@context' => $this->contextBuilder->getApiDocumentationContext(),
            '@id' => $this->router->generate('json_ld_api_vocab'),
            'hydra:title' => $this->title,
            'hydra:description' => $this->description,
            'hydra:entrypoint' => $this->router->generate('json_ld_api_entrypoint'),
            'hydra:supportedClass' => $classes,
        ];
    }

    /**
     * Returns data from $operation except when the key start with "!".
     *
     * @param array $operation
     *
     * @return array
     */
    private function getOperation(array $operation)
    {
        $supportedOperation = [];
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
