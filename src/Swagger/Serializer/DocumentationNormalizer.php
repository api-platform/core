<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Swagger\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    use FilterLocatorTrait;

    const SWAGGER_VERSION = '2.0';
    const FORMAT = 'json';
    const SWAGGER_DEFINITION_NAME = 'swagger_definition_name';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $operationPathResolver;
    private $nameConverter;
    private $oauthEnabled;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;
    private $apiKeys;
    private $subresourceOperationFactory;
    private $paginationEnabled;
    private $paginationPageParameterName;
    private $clientItemsPerPage;
    private $itemsPerPageParameterName;

    /**
     * @param ContainerInterface|FilterCollection|null $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, UrlGeneratorInterface $urlGenerator = null, $filterLocator = null, NameConverterInterface $nameConverter = null, $oauthEnabled = false, $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, $paginationEnabled = true, $paginationPageParameterName = 'page', $clientItemsPerPage = false, $itemsPerPageParameterName = 'itemsPerPage')
    {
        if ($urlGenerator) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.1 and will be removed in 3.0.', UrlGeneratorInterface::class, __METHOD__), E_USER_DEPRECATED);
        }

        $this->setFilterLocator($filterLocator, true);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->nameConverter = $nameConverter;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->apiKeys = $apiKeys;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
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

            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::COLLECTION);
            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::ITEM);

            if (null === $this->subresourceOperationFactory) {
                continue;
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $subresourceOperation) {
                $operationName = 'get';
                $subResourceMetadata = $this->resourceMetadataFactory->create($subresourceOperation['resource_class']);
                $serializerContext = $this->getSerializerContext(OperationType::SUBRESOURCE, false, $subResourceMetadata, $operationName);
                $responseDefinitionKey = $this->getDefinition($definitions, $subResourceMetadata, $subresourceOperation['resource_class'], $serializerContext);

                $pathOperation = new \ArrayObject([]);
                $pathOperation['tags'] = $subresourceOperation['shortNames'];
                $pathOperation['operationId'] = $operationId;
                $pathOperation['produces'] = $mimeTypes;
                $pathOperation['summary'] = sprintf('Retrieves %s%s resource%s.', $subresourceOperation['collection'] ? 'the collection of ' : 'a ', $subresourceOperation['shortNames'][0], $subresourceOperation['collection'] ? 's' : '');
                $pathOperation['responses'] = [
                    '200' => $subresourceOperation['collection'] ? [
                        'description' => sprintf('%s collection response', $subresourceOperation['shortNames'][0]),
                        'schema' => ['type' => 'array', 'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)]],
                    ] : [
                        'description' => sprintf('%s resource response', $subresourceOperation['shortNames'][0]),
                        'schema' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ],
                    '404' => ['description' => 'Resource not found'],
                ];

                // Avoid duplicates parameters when there is a filter on a subresource identifier
                $parametersMemory = [];
                $pathOperation['parameters'] = [];

                foreach ($subresourceOperation['identifiers'] as list($identifier, , $hasIdentifier)) {
                    if (true === $hasIdentifier) {
                        $pathOperation['parameters'][] = ['name' => $identifier, 'in' => 'path', 'required' => true, 'type' => 'string'];
                        $parametersMemory[] = $identifier;
                    }
                }

                if ($parameters = $this->getFiltersParameters($resourceClass, $operationName, $subResourceMetadata, $definitions, $serializerContext)) {
                    foreach ($parameters as $parameter) {
                        if (!\in_array($parameter['name'], $parametersMemory, true)) {
                            $pathOperation['parameters'][] = $parameter;
                        }
                    }
                }

                $paths[$this->getPath($subresourceOperation['shortNames'][0], $subresourceOperation['route_name'], $subresourceOperation, OperationType::SUBRESOURCE)] = new \ArrayObject(['get' => $pathOperation]);
            }
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($object, $definitions, $paths, $context);
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
     * @param string           $operationType
     */
    private function addPaths(\ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType)
    {
        if (null === $operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operationName, $operation, $operationType);
            $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

            $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, $operationType, $resourceClass, $resourceMetadata, $mimeTypes, $definitions);
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
     * @param string $operationName
     * @param array  $operation
     * @param string $operationType
     *
     * @return string
     */
    private function getPath(string $resourceShortName, string $operationName, array $operation, string $operationType): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
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
     * @param string           $operationType
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string[]         $mimeTypes
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function getPathOperation(string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes, \ArrayObject $definitions): \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation['swagger_context'] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($operationType);

        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'POST':
                return $this->updatePostOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
                // no break
            case 'PUT':
                return $this->updatePutOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'DELETE':
                return $this->updateDeleteOperation($pathOperation, $resourceShortName);
        }

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param string           $operationType
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updateGetOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $serializerContext = $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName);
        $responseDefinitionKey = $this->getDefinition($definitions, $resourceMetadata, $resourceClass, $serializerContext);

        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;

        if (OperationType::COLLECTION === $operationType) {
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

            if (!isset($pathOperation['parameters']) && $parameters = $this->getFiltersParameters($resourceClass, $operationName, $resourceMetadata, $definitions, $serializerContext)) {
                $pathOperation['parameters'] = $parameters;
            }

            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', true, true)) {
                $pathOperation['parameters'][] = $this->getPaginationParameters();

                if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                    $pathOperation['parameters'][] = $this->getItemsParPageParameters();
                }
            }

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'string',
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
     * @param string           $operationType
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updatePostOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => lcfirst($resourceShortName),
            'in' => 'body',
            'description' => sprintf('The new %s resource', $resourceShortName),
            'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName)
            ))],
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '201' => [
                'description' => sprintf('%s resource created', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName)
                ))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param string           $operationType
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function updatePutOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [
            [
                'name' => 'id',
                'in' => 'path',
                'type' => 'string',
                'required' => true,
            ],
            [
                'name' => lcfirst($resourceShortName),
                'in' => 'body',
                'description' => sprintf('The updated %s resource', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName)
                ))],
            ],
        ];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource updated', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName)
                ))],
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
    private function updateDeleteOperation(\ArrayObject $pathOperation, string $resourceShortName): \ArrayObject
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '204' => ['description' => sprintf('%s resource deleted', $resourceShortName)],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'type' => 'string',
            'required' => true,
        ]];

        return $pathOperation;
    }

    /**
     * @param \ArrayObject     $definitions
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param array|null       $serializerContext
     *
     * @return string
     */
    private function getDefinition(\ArrayObject $definitions, ResourceMetadata $resourceMetadata, string $resourceClass, array $serializerContext = null): string
    {
        if (isset($serializerContext[self::SWAGGER_DEFINITION_NAME])) {
            $definitionKey = sprintf('%s-%s', $resourceMetadata->getShortName(), $serializerContext[self::SWAGGER_DEFINITION_NAME]);
        } else {
            $definitionKey = $this->getDefinitionKey($resourceMetadata->getShortName(), (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []));
        }

        if (!isset($definitions[$definitionKey])) {
            $definitions[$definitionKey] = [];  // Initialize first to prevent infinite loop
            $definitions[$definitionKey] = $this->getDefinitionSchema($resourceClass, $resourceMetadata, $definitions, $serializerContext);
        }

        return $definitionKey;
    }

    private function getDefinitionKey(string $resourceShortName, array $groups): string
    {
        return $groups ? sprintf('%s-%s', $resourceShortName, implode('_', $groups)) : $resourceShortName;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getDefinitionSchema(string $resourceClass, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $definitionSchema = new \ArrayObject(['type' => 'object']);

        if (null !== $description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if (null !== $iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext[AbstractNormalizer::GROUPS]) ? ['serializer_groups' => $serializerContext[AbstractNormalizer::GROUPS]] : [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;

            if ($propertyMetadata->isRequired()) {
                $definitionSchema['required'][] = $normalizedPropertyName;
            }

            $definitionSchema['properties'][$normalizedPropertyName] = $this->getPropertySchema($propertyMetadata, $definitions, $serializerContext);
        }

        return $definitionSchema;
    }

    /**
     * Gets a property Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param PropertyMetadata $propertyMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getPropertySchema(PropertyMetadata $propertyMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $propertySchema = new \ArrayObject($propertyMetadata->getAttributes()['swagger_context'] ?? []);

        if (false === $propertyMetadata->isWritable()) {
            $propertySchema['readOnly'] = true;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        if (null === $type = $propertyMetadata->getType()) {
            return $propertySchema;
        }

        $isCollection = $type->isCollection();
        if (null === $valueType = $isCollection ? $type->getCollectionValueType() : $type) {
            $builtinType = 'string';
            $className = null;
        } else {
            $builtinType = $valueType->getBuiltinType();
            $className = $valueType->getClassName();
        }

        $valueSchema = $this->getType($builtinType, $isCollection, $className, $propertyMetadata->isReadableLink(), $definitions, $serializerContext);

        return new \ArrayObject((array) $propertySchema + $valueSchema);
    }

    /**
     * Gets the Swagger's type corresponding to the given PHP's type.
     *
     * @param string       $type
     * @param bool         $isCollection
     * @param string       $className
     * @param bool         $readableLink
     * @param \ArrayObject $definitions
     * @param array|null   $serializerContext
     *
     * @return array
     */
    private function getType(string $type, bool $isCollection, string $className = null, bool $readableLink = null, \ArrayObject $definitions, array $serializerContext = null): array
    {
        if ($isCollection) {
            return ['type' => 'array', 'items' => $this->getType($type, false, $className, $readableLink, $definitions, $serializerContext)];
        }

        if (Type::BUILTIN_TYPE_STRING === $type) {
            return ['type' => 'string'];
        }

        if (Type::BUILTIN_TYPE_INT === $type) {
            return ['type' => 'integer'];
        }

        if (Type::BUILTIN_TYPE_FLOAT === $type) {
            return ['type' => 'number'];
        }

        if (Type::BUILTIN_TYPE_BOOL === $type) {
            return ['type' => 'boolean'];
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type) {
            if (null === $className) {
                return ['type' => 'string'];
            }

            if (is_subclass_of($className, \DateTimeInterface::class)) {
                return ['type' => 'string', 'format' => 'date-time'];
            }

            if (!$this->resourceClassResolver->isResourceClass($className)) {
                return ['type' => 'string'];
            }

            if (true === $readableLink) {
                return ['$ref' => sprintf('#/definitions/%s', $this->getDefinition($definitions,
                    $this->resourceMetadataFactory->create($className),
                    $className, $serializerContext)
                )];
            }
        }

        return ['type' => 'string'];
    }

    /**
     * Computes the Swagger documentation.
     *
     * @param Documentation $documentation
     * @param \ArrayObject  $definitions
     * @param \ArrayObject  $paths
     * @param array         $context
     *
     * @return array
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths, array $context): array
    {
        $doc = [
            'swagger' => self::SWAGGER_VERSION,
            'basePath' => $context['base_url'] ?? '/',
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
        ];

        $securityDefinitions = [];
        $security = [];

        if ($this->oauthEnabled) {
            $securityDefinitions['oauth'] = [
                'type' => $this->oauthType,
                'description' => 'OAuth client_credentials Grant',
                'flow' => $this->oauthFlow,
                'tokenUrl' => $this->oauthTokenUrl,
                'authorizationUrl' => $this->oauthAuthorizationUrl,
                'scopes' => $this->oauthScopes,
            ];

            $security[] = ['oauth' => []];
        }

        if ($this->apiKeys) {
            foreach ($this->apiKeys as $key => $apiKey) {
                $name = $apiKey['name'];
                $type = $apiKey['type'];

                $securityDefinitions[$key] = [
                    'type' => 'apiKey',
                    'in' => $type,
                    'description' => sprintf('Value for the %s %s', $name, 'query' === $type ? sprintf('%s parameter', $type) : $type),
                    'name' => $name,
                ];

                $security[] = [$key => []];
            }
        }

        if ($securityDefinitions && $security) {
            $doc['securityDefinitions'] = $securityDefinitions;
            $doc['security'] = $security;
        }

        if ('' !== $description = $documentation->getDescription()) {
            $doc['info']['description'] = $description;
        }

        if (\count($definitions) > 0) {
            $doc['definitions'] = $definitions;
        }

        return $doc;
    }

    /**
     * Gets Swagger parameters corresponding to enabled filters.
     *
     * @param string           $resourceClass
     * @param string           $operationName
     * @param ResourceMetadata $resourceMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return array
     */
    private function getFiltersParameters(string $resourceClass, string $operationName, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): array
    {
        if (null === $this->filterLocator) {
            return [];
        }

        $parameters = [];
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $data['required'],
                ];
                $parameter += $this->getType($data['type'], false, null, null, $definitions, $serializerContext);

                if (isset($data['swagger'])) {
                    $parameter = $data['swagger'] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * Returns pagination parameters for the "get" collection operation.
     *
     * @return array
     */
    private function getPaginationParameters(): array
    {
        return [
            'name' => $this->paginationPageParameterName,
            'in' => 'query',
            'required' => false,
            'type' => 'integer',
            'description' => 'The collection page number',
        ];
    }

    /**
     * Returns items per page parameters for the "get" collection operation.
     *
     * @return array
     */
    private function getItemsParPageParameters(): array
    {
        return [
            'name' => $this->itemsPerPageParameterName,
            'in' => 'query',
            'required' => false,
            'type' => 'integer',
            'description' => 'The number of items per page',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    /**
     * @param string           $operationType
     * @param bool             $denormalization
     * @param ResourceMetadata $resourceMetadata
     * @param string           $operationType
     *
     * @return array|null
     */
    private function getSerializerContext(string $operationType, bool $denormalization, ResourceMetadata $resourceMetadata, string $operationName)
    {
        $contextKey = $denormalization ? 'denormalization_context' : 'normalization_context';

        if (OperationType::COLLECTION === $operationType) {
            return $resourceMetadata->getCollectionOperationAttribute($operationName, $contextKey, null, true);
        }

        return $resourceMetadata->getItemOperationAttribute($operationName, $contextKey, null, true);
    }
}
