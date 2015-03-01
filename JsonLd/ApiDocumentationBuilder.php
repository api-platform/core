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

    public function getApiDocumentation()
    {
        $doc = [
            '@context' => $this->router->generate('json_ld_api_context', ['shortName' => 'ApiDocumentation']),
            '@id' => $this->router->generate('json_ld_api_vocab'),
            'hydra:title' => $this->title,
            'hydra:description' => $this->description,
            'hydra:entrypoint' => $this->router->generate('json_ld_api_entrypoint'),
            'hydra:supportedClass' => [],
        ];

        // Constraint violation
        $doc['hydra:supportedClass'][] = [
            '@id' => 'ConstraintViolation',
            '@type' => 'hydra:class',
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
        $doc['hydra:supportedClass'][] = [
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

        // Entrypoint
        $supportedProperties = [];
        foreach ($this->resources as $resource) {
            $shortName = $resource->getShortName();

            $supportedProperty = [
                '@type' => 'hydra:SupportedProperty',
                'hydra:property' => [
                    '@id' => lcfirst($resource->getBeautifiedName()),
                    '@type' => 'rdf:Property',
                    'rdfs:label' => sprintf('The collection of %s resources', $shortName),
                    'domain' => 'Entrypoint',
                    'range' => $resource->getBeautifiedName(),
                ],
                'hydra:title' => sprintf('The collection of %s resources', $shortName),
                'hydra:readable' => true,
                'hydra:writable' => false,
                'hydra:supportedOperation' => [],
            ];

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

                $this->populateSupportedOperation($supportedOperation, $operation);

                $supportedProperty['hydra:supportedOperation'][] = $supportedOperation;
            }

            $supportedProperties[] = $supportedProperty;
        }

        $doc['hydra:supportedClass'][] = [
            '@id' => 'Entrypoint',
            '@type' => 'hydra:class',
            'hydra:title' => 'The API entrypoint',
            'hydra:supportedProperty' => $supportedProperties,
        ];

        // Resources
        foreach ($this->resources as $resource) {
            $metadata = $this->classMetadataFactory->getMetadataFor($resource->getEntityClass());
            $shortName = $resource->getShortName();

            $supportedClass = [
                '@id' => $shortName,
                '@type' => 'hydra:Class',
                'hydra:title' => $resource->getShortName(),
            ];

            $description = $metadata->getDescription();
            if ($description) {
                $supportedClass['hydra:description'] = $description;
            }

            $attributes = $metadata->getAttributes(
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            );

            $supportedClass['hydra:supportedProperty'] = [];
            foreach ($attributes as $name => $details) {
                $supportedProperty = [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => sprintf('%s/%s', $shortName, $name),
                        '@type' => 'rdf:Property',
                        'rdfs:label' => $name,
                        'domain' => $shortName,
                    ],
                    'hydra:title' => $name,
                    'hydra:required' => $details['required'],
                    'hydra:readable' => $details['readable'],
                    'hydra:writable' => $details['writable'],
                ];

                if ($details['description']) {
                    $supportedProperty['hydra:description'] = $details['description'];
                }

                $supportedClass['hydra:supportedProperty'][] = $supportedProperty;
            }

            $supportedClass['hydra:supportedOperation'] = [];
            foreach ($resource->getItemOperations() as $operation) {
                $supportedOperation = [];

                if ('PUT' === $operation['hydra:method']) {
                    $supportedOperation['@type'] = 'hydra:ReplaceResourceOperation';
                    $supportedOperation['hydra:title'] = sprintf('Replaces the %s resource.', $shortName);
                    $supportedOperation['hydra:expects'] = $shortName;
                    $supportedOperation['hydra:returns'] = $shortName;
                } elseif ('DELETE' === $operation['hydra:method']) {
                    $supportedOperation['@type'] = 'hydra:Operation';
                    $supportedOperation['hydra:title'] = sprintf('Deletes the %s resource.', $shortName);
                    $supportedOperation['hydra:expects'] = $shortName;
                } else {
                    if ('GET' === $operation['hydra:method']) {
                        $supportedOperation['@type'] = 'hydra:Operation';
                        $supportedOperation['hydra:title'] = sprintf('Retrieves %s resource.', $shortName);
                        $supportedOperation['hydra:returns'] = $shortName;
                    }
                }

                $this->populateSupportedOperation($supportedOperation, $operation);

                $supportedClass['hydra:supportedOperation'][] = $supportedOperation;
            }

            $doc['hydra:supportedClass'][] = $supportedClass;
        }

        return $doc;
    }

    /**
     * Copies data from $operation to $supportedOperation except when the key start with "!".
     *
     * @param array $supportedOperation
     * @param array $operation
     */
    private function populateSupportedOperation(array &$supportedOperation, array $operation)
    {
        foreach ($operation as $key => $value) {
            if (isset($key[0]) && '!' !== $key[0]) {
                $supportedOperation[$key] = $value;
            }
        }
    }
}
