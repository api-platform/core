<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Serializer;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Swagger API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    const SWAGGER_VERSION = '2.0';
    const FORMAT = 'json';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $operationPathResolver;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $definitions = new \ArrayObject();
        $paths = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();
            $definitions[$resourceShortName] = $this->getDefinitionSchema($resourceClass, $resourceMetadata);

            foreach ($resourceMetadata->getCollectionOperations() ?? [] as $operationName => $operation) {
                $path = $this->getPath($resourceShortName, $operation, true);
                $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

                $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, true, $resourceMetadata, $object->getMimeTypes());
            }

            foreach ($resourceMetadata->getItemOperations() ?? [] as $operationName => $operation) {
                $path = $this->getPath($resourceShortName, $operation, false);
                $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);

                $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, false, $resourceMetadata, $object->getMimeTypes());
            }
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($object, $definitions, $paths);
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     *
     * @param string $resourceShortName
     * @param array  $operation
     * @param bool   $collection
     *
     * @return string
     */
    private function getPath(string $resourceShortName, array $operation, bool $collection) : string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $collection);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets a path Operation Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operation-object
     *
     * @param string           $operationName
     * @param array            $operation
     * @param string           $method
     * @param bool             $collection
     * @param ResourceMetadata $resourceMetadata
     * @param string[]         $mimeTypes
     *
     * @return \ArrayObject
     */
    private function getPathOperation(string $operationName, array $operation, string $method, bool $collection, ResourceMetadata $resourceMetadata, array $mimeTypes) : \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation['swagger_context'] ?? []);

        $resourceShortName = $resourceMetadata->getShortName();

        if (!isset($pathOperation['tags'])) {
            $pathOperation['tags'] = [
                $resourceShortName,
            ];
        }

        if (!isset($pathOperation['operationId'])) {
            $pathOperation['operationId'] = sprintf('%s%s%s', lcfirst($operationName), ucfirst($resourceShortName), ucfirst($collection ? 'collection' : 'item'));
        }

        switch ($method) {
            case 'GET':
                if (!isset($pathOperation['produces'])) {
                    $pathOperation['produces'] = $mimeTypes;
                }

                if ($collection) {
                    if (!isset($pathOperation['summary'])) {
                        $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceShortName);
                    }

                    if (!isset($pathOperation['responses'])) {
                        $pathOperation['responses'] = [
                            '200' => [
                                'description' => sprintf('%s collection response', $resourceShortName),
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                                    ],
                                ],
                            ],
                        ];
                    }
                } else {
                    if (!isset($pathOperation['summary'])) {
                        $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);
                    }

                    if (!isset($pathOperation['parameters'])) {
                        $pathOperation['parameters'] = [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ];
                    }

                    if (!isset($pathOperation['responses'])) {
                        $pathOperation['responses'] = [
                            '200' => [
                                'description' => sprintf('%s resource response', $resourceShortName),
                                'schema' => [
                                    '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                                ],
                            ],
                            '404' => [
                                'description' => 'Resource not found',
                            ],
                        ];
                    }
                }
                break;

            case 'POST':
                if (!isset($pathOperation['consumes'])) {
                    $pathOperation['consumes'] = $mimeTypes;
                }
                if (!isset($pathOperation['produces'])) {
                    $pathOperation['produces'] = $mimeTypes;
                }

                if (!isset($pathOperation['summary'])) {
                    $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);
                }

                if (!isset($pathOperation['parameters'])) {
                    $pathOperation['parameters'] = [
                        [
                            'name' => lcfirst($resourceShortName),
                            'in' => 'body',
                            'description' => sprintf('The new %s resource', $resourceShortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                            ],
                        ],
                    ];
                }

                if (!isset($pathOperation['responses'])) {
                    $pathOperation['responses'] = [
                        '201' => [
                            'description' => sprintf('%s resource created', $resourceShortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                            ],
                        ],
                        '400' => [
                            'description' => 'Invalid input',
                        ],
                        '404' => [
                            'description' => 'Resource not found',
                        ],
                    ];
                }
                break;

            case 'PUT':
                if (!isset($pathOperation['consumes'])) {
                    $pathOperation['consumes'] = $mimeTypes;
                }
                if (!isset($pathOperation['produces'])) {
                    $pathOperation['produces'] = $mimeTypes;
                }

                if (!isset($pathOperation['summary'])) {
                    $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);
                }

                if (!isset($pathOperation['parameters'])) {
                    $pathOperation['parameters'] = [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                        [
                            'name' => lcfirst($resourceShortName),
                            'in' => 'body',
                            'description' => sprintf('The updated %s resource', $resourceShortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                            ],
                        ],
                    ];
                }

                if (!isset($pathOperation['responses'])) {
                    $pathOperation['responses'] = [
                        '200' => [
                            'description' => sprintf('%s resource updated', $resourceShortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $resourceShortName),
                            ],
                        ],
                        '400' => [
                            'description' => 'Invalid input',
                        ],
                        '404' => [
                            'description' => 'Resource not found',
                        ],
                    ];
                }
                break;

            case 'DELETE':
                if (!isset($pathOperation['summary'])) {
                    $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
                }

                if (!isset($pathOperation['responses'])) {
                    $pathOperation['responses'] = [
                        '204' => [
                            'description' => sprintf('%s resource deleted', $resourceShortName),
                        ],
                        '404' => [
                            'description' => 'Resource not found',
                        ],
                    ];
                }

                if (!isset($pathOperation['parameters'])) {
                    $pathOperation['parameters'] = [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                    ];
                }
                break;

            default:
                break;
        }

        return $pathOperation;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     *
     * @return \ArrayObject
     */
    private function getDefinitionSchema(string $resourceClass, ResourceMetadata $resourceMetadata) : \ArrayObject
    {
        $definitionSchema = new \ArrayObject([
            'type' => 'object',
        ]);

        if ($description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if ($iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = [
                'url' => $iri,
            ];
        }

        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context']['groups'])) {
            $context['serializer_groups'] = $attributes['normalization_context']['groups'];
        }

        if (isset($attributes['denormalization_context']['groups'])) {
            $context['serializer_groups'] = isset($context['serializer_groups']) ? array_merge($context['serializer_groups'], $attributes['denormalization_context']['groups']) : $context['serializer_groups'];
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $context) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyMetadata->isRequired()) {
                $definitionSchema['required'][] = $propertyName;
            }

            $definitionSchema['properties'][$propertyName] = $this->getPropertySchema($propertyMetadata);
        }

        return $definitionSchema;
    }

    /**
     * Gets a property Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param PropertyMetadata $propertyMetadata
     *
     * @return \ArrayObject
     */
    private function getPropertySchema(PropertyMetadata $propertyMetadata) : \ArrayObject
    {
        $propertySchema = new \ArrayObject();

        if ($description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        $type = $propertyMetadata->getType();
        if (!$type) {
            return $propertySchema;
        }

        $valueSchema = new \ArrayObject();

        $valueType = $type->isCollection() ? $type->getCollectionValueType() : $type;

        switch ($valueType ? $valueType->getBuiltinType() : null) {
            case Type::BUILTIN_TYPE_STRING:
                $valueSchema['type'] = 'string';
                break;

            case Type::BUILTIN_TYPE_INT:
                $valueSchema['type'] = 'integer';
                break;

            case Type::BUILTIN_TYPE_FLOAT:
                $valueSchema['type'] = 'number';
                break;

            case Type::BUILTIN_TYPE_BOOL:
                $valueSchema['type'] = 'boolean';
                break;

            case Type::BUILTIN_TYPE_OBJECT:
                $className = $valueType->getClassName();
                if (null === $className) {
                    break;
                }

                if (is_subclass_of($className, \DateTimeInterface::class)) {
                    $valueSchema['type'] = 'string';
                    $valueSchema['format'] = 'date-time';
                    break;
                }

                if (!$this->resourceClassResolver->isResourceClass($className)) {
                    break;
                }

                if ($propertyMetadata->isReadableLink()) {
                    $valueSchema['$ref'] = sprintf('#/definitions/%s', $this->resourceMetadataFactory->create($className)->getShortName());
                    break;
                }

                $valueSchema['type'] = 'string';
                $valueSchema['format'] = 'uri';
                break;

            default:
                break;
        }

        if ($type->isCollection()) {
            $propertySchema['type'] = 'array';
            $propertySchema['items'] = $valueSchema;
        } else {
            $propertySchema = new \ArrayObject(array_merge((array) $propertySchema, (array) $valueSchema));
        }

        return $propertySchema;
    }

    private function computeDoc(Documentation $object, \ArrayObject $definitions, \ArrayObject $paths): array
    {
        $doc = [
            'swagger' => self::SWAGGER_VERSION,
            'info' => ['title' => $object->getTitle()],
        ];

        if ('' !== $object->getDescription()) {
            $doc['info']['description'] = $object->getDescription();
        }

        $doc['info']['version'] = $object->getVersion();
        $doc['paths'] = $paths;

        if (count($definitions) > 0) {
            $doc['definitions'] = $definitions;
        }

        return $doc;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}
