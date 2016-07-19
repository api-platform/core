<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
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

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $iriConverter;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, IriConverterInterface $iriConverter)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $classes = [];
        $operation = [];
        $customOperations = [];
        $itemOperationsDocs = [];
        $definitions = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $operation['item'] = [];
            $operation['collection'] = [];
            $customOperations['item'] = [];
            $customOperations['collection'] = [];
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = ($iri = $resourceMetadata->getIri()) ? $iri : '#'.$shortName;

            $class = [
                'name' => $shortName,
                'externalDocs' => ['url' => $prefixedShortName],
            ];

            if ($description = $resourceMetadata->getDescription()) {
                $class = [
                    'name' => $shortName,
                    'description' => $description,
                    'externalDocs' => ['url' => $prefixedShortName],
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

            $definitions[$shortName] = [
                'type' => 'object',
                'xml' => ['name' => 'response'],
            ];

            foreach ($this->propertyNameCollectionFactory->create($resourceClass, $context) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

                if ($propertyMetadata->isRequired()) {
                    $definitions[$shortName]['required'][] = $propertyName;
                }

                $range = $this->getRange($propertyMetadata);
                if (empty($range)) {
                    continue;
                }

                if ($propertyMetadata->getDescription()) {
                    $definitions[$shortName]['properties'][$propertyName]['description'] = $propertyMetadata->getDescription();
                }

                if ($range['complex']) {
                    $definitions[$shortName]['properties'][$propertyName] = ['$ref' => $range['value']];
                } else {
                    $definitions[$shortName]['properties'][$propertyName] = ['type' => $range['value']];

                    if (isset($range['example'])) {
                        $definitions[$shortName]['properties'][$propertyName]['example'] = $range['example'];
                    }
                }
            }

            if ($operations = $resourceMetadata->getItemOperations()) {
                foreach ($operations as $operationName => $itemOperation) {
                    $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
                    $swaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $operationName, $itemOperation, $prefixedShortName, false, $definitions, $method, $object->getMimeTypes());
                    $operation['item'] = array_merge($operation['item'], $swaggerOperation);
                    if ($operationName !== strtolower($method)) {
                        $customOperations['item'][] = $operationName;
                    }
                }
            }

            if ($operations = $resourceMetadata->getCollectionOperations()) {
                foreach ($operations as $operationName => $collectionOperation) {
                    $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
                    $swaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $operationName, $collectionOperation, $prefixedShortName, true, $definitions, $method, $object->getMimeTypes());
                    $operation['collection'] = array_merge($operation['collection'], $swaggerOperation);
                    if ($operationName !== strtolower($method)) {
                        $customOperations['collection'][] = $operationName;
                    }
                }
            }
            try {
                $resourceClassIri = $this->iriConverter->getIriFromResourceClass($resourceClass);
                $itemOperationsDocs[$resourceClassIri] = $operation['collection'];

                if (!empty($customOperations['collection'])) {
                    foreach ($customOperations['collection'] as $customOperation) {
                        $path = $resourceMetadata->getCollectionOperationAttribute($customOperation, 'path');
                        if (null !== $path) {
                            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $customOperation);
                            $customSwaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $customOperation, [$method], $prefixedShortName, true, $definitions, $method, $object->getMimeTypes());
                            if (!isset($operation['collection'][strtolower($method)])) {
                                var_dump($operation['collection'][strtolower($method)]);
                                unset($itemOperationsDocs[$resourceClassIri]);
                            }
                            $itemOperationsDocs[$path] = $customSwaggerOperation;
                        }
                    }
                }

                $resourceClassIri .= '/{id}';

                $itemOperationsDocs[$resourceClassIri] = $operation['item'];

                if (!empty($customOperations['item'])) {
                    foreach ($customOperations['item'] as $customOperation) {
                        $path = $resourceMetadata->getItemOperationAttribute($customOperation, 'path');
                        if (null !== $path) {
                            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $customOperation);
                            $customSwaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $customOperation, [$method], $prefixedShortName, true, $definitions, $method, $object->getMimeTypes());
                            var_dump($resourceMetadata->getItemOperationAttribute($customOperation, $method));
                            if (!isset($operation['item'][strtolower($method)])) {
                                var_dump($operation['item'][strtolower($method)]);
                                unset($itemOperationsDocs[$resourceClassIri]);
                            }
                            $itemOperationsDocs[$path] = $customSwaggerOperation;
                        }
                    }
                }
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $classes[] = $class;
        }

        $doc['swagger'] = self::SWAGGER_VERSION;
        if ('' !== $object->getTitle()) {
            $doc['info']['title'] = $object->getTitle();
        }

        if ('' !== $object->getDescription()) {
            $doc['info']['description'] = $object->getDescription();
        }
        $doc['info']['version'] = $object->getVersion() ?? '0.0.0';
        $doc['definitions'] = $definitions;
        $doc['externalDocs'] = ['description' => 'Find more about API Platform', 'url' => 'https://api-platform.com'];
        $doc['tags'] = $classes;
        $doc['paths'] = $itemOperationsDocs;

        return $doc;
    }

    /**
     * Gets and populates if applicable a Swagger operation.
     */
    private function getSwaggerOperation(string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $operation, string $prefixedShortName, bool $collection, array $properties, string $method, array $mimeTypes) : array
    {
        $methodSwagger = strtolower($method);
        $swaggerOperation = $operation['swagger_context'] ?? [];
        $shortName = $resourceMetadata->getShortName();
        $swaggerOperation[$methodSwagger] = [];
        $swaggerOperation[$methodSwagger]['tags'] = [$shortName];

        switch ($method) {
            case 'GET':
                $swaggerOperation[$methodSwagger]['produces'] = $mimeTypes;

                if ($collection) {
                    if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                        $swaggerOperation[$methodSwagger]['summary'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                    }

                    $swaggerOperation[$methodSwagger]['responses'] = [
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

                if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                    $swaggerOperation[$methodSwagger]['summary'] = sprintf('Retrieves %s resource.', $shortName);
                }

                $swaggerOperation[$methodSwagger]['parameters'][] = [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'type' => 'integer',
                ];

                $swaggerOperation[$methodSwagger]['responses'] = [
                    '200' => [
                        'description' => 'Successful operation',
                        'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                    ],
                    '404' => ['description' => 'Resource not found'],
                ];
                break;

            case 'POST':
                $swaggerOperation[$methodSwagger]['consumes'] = $swaggerOperation[$methodSwagger]['produces'] = $mimeTypes;

                if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                    $swaggerOperation[$methodSwagger]['summary'] = sprintf('Creates a %s resource.', $shortName);
                }

                if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                    $swaggerOperation[$methodSwagger]['parameters'][] = [
                        'in' => 'body',
                        'name' => 'body',
                        'description' => sprintf('%s resource to be added', $shortName),
                        'schema' => [
                            '$ref' => sprintf('#/definitions/%s', $shortName),
                        ],
                    ];
                }

                $swaggerOperation[$methodSwagger]['responses'] = [
                        '201' => [
                            'description' => 'Successful operation',
                            'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                        ],
                        '400' => ['description' => 'Invalid input'],
                        '404' => ['description' => 'Resource not found'],
                ];
            break;

            case 'PUT':
                $swaggerOperation[$methodSwagger]['consumes'] = $swaggerOperation[$methodSwagger]['produces'] = $mimeTypes;

                if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                    $swaggerOperation[$methodSwagger]['summary'] = sprintf('Replaces the %s resource.', $shortName);
                }

                if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                    $swaggerOperation[$methodSwagger]['parameters'] = [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'type' => 'integer',
                        ],
                        [
                            'in' => 'body',
                            'name' => 'body',
                            'description' => sprintf('%s resource to be added', $shortName),
                            'schema' => [
                                '$ref' => sprintf('#/definitions/%s', $shortName),
                            ],
                        ],
                    ];
                }

                $swaggerOperation[$methodSwagger]['responses'] = [
                    '200' => [
                        'description' => 'Successful operation',
                        'schema' => ['$ref' => sprintf('#/definitions/%s', $shortName)],
                    ],
                    '400' => ['description' => 'Invalid input'],
                    '404' => ['description' => 'Resource not found'],
                ];
            break;

            case 'DELETE':
                $swaggerOperation[$methodSwagger]['responses'] = [
                    '204' => ['description' => 'Deleted'],
                    '404' => ['description' => 'Resource not found'],
                ];

                $swaggerOperation[$methodSwagger]['parameters'] = [[
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
     * Gets the range of the property.
     *
     * @param PropertyMetadata $propertyMetadata
     *
     * @return array|null
     */
    private function getRange(PropertyMetadata $propertyMetadata) : array
    {
        $type = $propertyMetadata->getType();
        if (!$type) {
            return [];
        }

        if ($type->isCollection() && $collectionType = $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return ['complex' => false, 'value' => 'string'];

            case Type::BUILTIN_TYPE_INT:
                return ['complex' => false, 'value' => 'integer'];

            case Type::BUILTIN_TYPE_FLOAT:
                return ['complex' => false, 'value' => 'number'];

            case Type::BUILTIN_TYPE_BOOL:
                return ['complex' => false, 'value' => 'boolean'];

            case Type::BUILTIN_TYPE_OBJECT:
                $className = $type->getClassName();
                if (null === $className) {
                    return [];
                }

                if (is_subclass_of($className, \DateTimeInterface::class)) {
                    return ['complex' => false, 'value' => 'string', 'example' => '1988-01-21T00:00:00+00:00'];
                }

                if (!$this->resourceClassResolver->isResourceClass($className)) {
                    return [];
                }

                if ($propertyMetadata->isReadableLink()) {
                    return ['complex' => true, 'value' => sprintf('#/definitions/%s', $this->resourceMetadataFactory->create($className)->getShortName())];
                }

                return ['complex' => false, 'value' => 'string', 'example' => '/my/iri'];

            default:
                return ['complex' => false, 'value' => 'null'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}
