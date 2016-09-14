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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
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
    private $urlGenerator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, UrlGeneratorInterface $urlGenerator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $mimeTypes = $object->getMimeTypes();
        $definitions = new \ArrayObject();
        $paths = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();

            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, true);
            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, false);
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($object, $definitions, $paths);
    }

    /**
     * Updates the list of entries in the paths collection.
     *
     * @param \ArrayObject     $paths
     * @param \ArrayObject     $definitions
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param ResourceMetadata $resourceMetadata
     * @param array            $mimeTypes
     * @param bool             $collection
     */
    private function addPaths(\ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, bool $collection)
    {
        if (null === $operations = $collection ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operation, $collection);
            $method = $collection ? $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);

            $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, $collection, $resourceClass, $resourceMetadata, $mimeTypes, $definitions);
        }
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
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string[]         $mimeTypes
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function getPathOperation(string $operationName, array $operation, string $method, bool $collection, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes, \ArrayObject $definitions) : \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation['swagger_context'] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($collection ? 'collection' : 'item');

        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($pathOperation, $mimeTypes, $collection, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);

            case 'POST':
                return $this->updatePostOperation($pathOperation, $mimeTypes, $collection, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);

            case 'PUT':
                return $this->updatePutOperation($pathOperation, $mimeTypes, $collection, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);

            case 'DELETE':
                return $this->updateDeleteOperation($pathOperation, $resourceShortName);
        }

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param bool             $collection
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updateGetOperation(\ArrayObject $pathOperation, array $mimeTypes, bool $collection, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $responseDefinitionKey = $this->getDefinition($definitions, $collection, false, $resourceMetadata, $resourceClass, $operationName);
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;

        if ($collection) {
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceShortName);
            $pathOperation['responses'] ?? $pathOperation['responses'] = [
                '200' => [
                    'description' => sprintf('%s collection response', $resourceShortName),
                    'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ],
                ],
            ];

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'integer',
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource response', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
            ],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param bool             $collection
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updatePostOperation(\ArrayObject $pathOperation, array $mimeTypes, bool $collection, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => lcfirst($resourceShortName),
            'in' => 'body',
            'description' => sprintf('The new %s resource', $resourceShortName),
            'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $collection, true, $resourceMetadata, $resourceClass, $operationName))],
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '201' => [
                'description' => sprintf('%s resource created', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $collection, false, $resourceMetadata, $resourceClass, $operationName))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param bool             $collection
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updatePutOperation(\ArrayObject $pathOperation, array $mimeTypes, bool $collection, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [
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
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $collection, true, $resourceMetadata, $resourceClass, $operationName))],
            ],
        ];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource updated', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $collection, false, $resourceMetadata, $resourceClass, $operationName))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject $pathOperation
     * @param string       $resourceShortName
     *
     * @return \ArrayObject
     */
    private function updateDeleteOperation(\ArrayObject $pathOperation, string $resourceShortName) : \ArrayObject
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '204' => ['description' => sprintf('%s resource deleted', $resourceShortName)],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'type' => 'integer',
            'required' => true,
        ]];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $definitions
     * @param bool             $collection
     * @param bool             $parameter
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $operationName
     *
     * @return string
     */
    private function getDefinition(\ArrayObject $definitions, bool $collection, bool $parameter, ResourceMetadata $resourceMetadata, string $resourceClass, string $operationName) : string
    {
        $contextKey = $parameter ? 'denormalization_context' : 'normalization_context';
        $serializerContext = $collection ? $resourceMetadata->getCollectionOperationAttribute($operationName, $contextKey, null, true) : $resourceMetadata->getItemOperationAttribute($operationName, $contextKey, null, true);

        if (isset($serializerContext['groups'])) {
            $definitionKey = sprintf('%s_%s', $resourceMetadata->getShortName(), md5(serialize($serializerContext['groups'])));
        } else {
            $definitionKey = $resourceMetadata->getShortName();
        }

        $definitions[$definitionKey] = $this->getDefinitionSchema($resourceClass, $resourceMetadata, $serializerContext);

        return $definitionKey;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getDefinitionSchema(string $resourceClass, ResourceMetadata $resourceMetadata, array $serializerContext = null) : \ArrayObject
    {
        $definitionSchema = new \ArrayObject(['type' => 'object']);

        if (null !== $description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if (null !== $iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext['groups']) ? ['serializer_groups' => $serializerContext['groups']] : [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $options) as $propertyName) {
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

        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        if (null == $type = $propertyMetadata->getType()) {
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
                if (null === $className = $valueType->getClassName()) {
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

                if (true === $propertyMetadata->isReadableLink()) {
                    $valueSchema['$ref'] = sprintf('#/definitions/%s', $this->resourceMetadataFactory->create($className)->getShortName());
                    break;
                }

                $valueSchema['type'] = 'string';
                $valueSchema['format'] = 'uri';
                break;
        }

        if ($type->isCollection()) {
            $propertySchema['type'] = 'array';
            $propertySchema['items'] = $valueSchema;
        } else {
            $propertySchema = new \ArrayObject((array) $propertySchema + (array) $valueSchema);
        }

        return $propertySchema;
    }

    /**
     * Computes the Swagger documentation.
     *
     * @param Documentation $documentation
     * @param \ArrayObject  $definitions
     * @param \ArrayObject  $paths
     *
     * @return array
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths) : array
    {
        $doc = [
            'swagger' => self::SWAGGER_VERSION,
            'basePath' => $this->urlGenerator->generate('api_entrypoint'),
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
        ];

        if ('' !== $description = $documentation->getDescription()) {
            $doc['info']['description'] = $description;
        }

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
