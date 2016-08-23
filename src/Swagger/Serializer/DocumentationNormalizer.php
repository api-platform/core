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
        $itemOperationsDocs = [];
        $definitions = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $shortName = $resourceMetadata->getShortName();

            $definitions[$shortName] = $this->getDefinitionSchema($resourceClass, $resourceMetadata);

            $operations = $resourceMetadata->getCollectionOperations() ?? [];
            foreach ($operations as $operationName => $collectionOperation) {
                $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
                $path = $this->getPath($shortName, $collectionOperation, true);

                $swaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $collectionOperation, true, $method, $object->getMimeTypes());
                $itemOperationsDocs[$path] = array_merge($itemOperationsDocs[$path] ?? [], $swaggerOperation);
            }

            $operations = $resourceMetadata->getItemOperations() ?? [];
            foreach ($operations as $operationName => $itemOperation) {
                $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
                $path = $this->getPath($shortName, $itemOperation, false);

                $swaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $itemOperation, false, $method, $object->getMimeTypes());
                $itemOperationsDocs[$path] = array_merge($itemOperationsDocs[$path] ?? [], $swaggerOperation);
            }
        }

        return $this->computeDoc($object, $definitions, $itemOperationsDocs);
    }

    private function getPath(string $resourceShortName, array $operation, bool $collection) : string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $collection);
        if (substr($path, -10) === '.{_format}') {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets and populates if applicable a Swagger operation.
     */
    private function getSwaggerOperation(string $resourceClass, ResourceMetadata $resourceMetadata, array $operation, bool $collection, string $method, array $mimeTypes) : array
    {
        $swaggerMethod = strtolower($method);
        $swaggerOperation = $operation['swagger_context'] ?? [];
        $shortName = $resourceMetadata->getShortName();
        $swaggerOperation[$swaggerMethod] = [];
        $swaggerOperation[$swaggerMethod]['tags'] = [$shortName];

        switch ($method) {
            case 'GET':
                $swaggerOperation[$swaggerMethod]['produces'] = $mimeTypes;

                if ($collection) {
                    if (!isset($swaggerOperation[$swaggerMethod]['title'])) {
                        $swaggerOperation[$swaggerMethod]['summary'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                    }

                    $swaggerOperation[$swaggerMethod]['responses'] = [
                        '200' => [
                            'description' => 'Successful operation',
                             'schema' => [
                                'type' => 'array',
                                'items' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                             ],
                        ],
                    ];
                    break;
                }

                if (!isset($swaggerOperation[$swaggerMethod]['title'])) {
                    $swaggerOperation[$swaggerMethod]['summary'] = sprintf('Retrieves a %s resource.', $shortName);
                }

                $swaggerOperation[$swaggerMethod]['parameters'][] = [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'type' => 'integer',
                ];

                $swaggerOperation[$swaggerMethod]['responses'] = [
                    '200' => [
                        'description' => 'Successful operation',
                        'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                    ],
                    '404' => ['description' => 'Resource not found'],
                ];
                break;

            case 'POST':
                $swaggerOperation[$swaggerMethod]['consumes'] = $swaggerOperation[$swaggerMethod]['produces'] = $mimeTypes;

                if (!isset($swaggerOperation[$swaggerMethod]['title'])) {
                    $swaggerOperation[$swaggerMethod]['summary'] = sprintf('Creates a %s resource.', $shortName);
                }

                if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                    $swaggerOperation[$swaggerMethod]['parameters'][] = [
                        'in' => 'body',
                        'name' => 'body',
                        'description' => sprintf('The new %s resource', $shortName),
                        'schema' => [
                            '$ref' => sprintf('#/definitions/%s', $shortName),
                        ],
                    ];
                }

                $swaggerOperation[$swaggerMethod]['responses'] = [
                        '201' => [
                            'description' => 'Successful operation',
                            'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                        ],
                        '400' => ['description' => 'Invalid input'],
                        '404' => ['description' => 'Resource not found'],
                ];
                break;

            case 'PUT':
                $swaggerOperation[$swaggerMethod]['consumes'] = $swaggerOperation[$swaggerMethod]['produces'] = $mimeTypes;

                if (!isset($swaggerOperation[$swaggerMethod]['title'])) {
                    $swaggerOperation[$swaggerMethod]['summary'] = sprintf('Replaces the %s resource.', $shortName);
                }

                if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                    $swaggerOperation[$swaggerMethod]['parameters'] = [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'type' => 'integer',
                        ],
                        [
                            'in' => 'body',
                            'name' => 'body',
                            'description' => sprintf('The updated %s resource', $shortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $shortName),
                            ],
                        ],
                    ];
                }

                $swaggerOperation[$swaggerMethod]['responses'] = [
                    '200' => [
                        'description' => 'Successful operation',
                        'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                    ],
                    '400' => ['description' => 'Invalid input'],
                    '404' => ['description' => 'Resource not found'],
                ];
                break;

            case 'DELETE':
                if (!isset($swaggerOperation[$swaggerMethod]['title'])) {
                    $swaggerOperation[$swaggerMethod]['summary'] = sprintf('Removes the %s resource.', $shortName);
                }

                $swaggerOperation[$swaggerMethod]['responses'] = [
                    '204' => ['description' => 'Deleted'],
                    '404' => ['description' => 'Resource not found'],
                ];

                $swaggerOperation[$swaggerMethod]['parameters'] = [[
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'type' => 'integer',
                ]];
                break;
        }
        ksort($swaggerOperation);

        return $swaggerOperation;
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

    private function computeDoc(Documentation $object, array $definitions, array $itemOperationsDocs): array
    {
        $doc['swagger'] = self::SWAGGER_VERSION;
        $doc['info']['title'] = $object->getTitle();
        if ('' !== $object->getDescription()) {
            $doc['info']['description'] = $object->getDescription();
        }
        $doc['info']['version'] = $object->getVersion();
        $doc['definitions'] = $definitions;
        $doc['paths'] = $itemOperationsDocs;

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
