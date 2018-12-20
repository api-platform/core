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

namespace ApiPlatform\Core\OpenApi\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable OpenAPI 3.0 documentation.
 *
 * @experimental
 *
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class DocumentationNormalizer extends AbstractDocumentationNormalizer
{
    use FilterLocatorTrait;

    const OPENAPI_VERSION = '3.0.2';
    const SWAGGER_DEFINITION_NAME = 'swagger_definition_name';

    private $legacyNormalizer;

    /**
     * @param ContainerInterface|FilterCollection|null $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, $filterLocator = null, NameConverterInterface $nameConverter = null, bool $oauthEnabled = false, string $oauthType = '', string $oauthFlow = '', string $oauthTokenUrl = '', string $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, bool $paginationEnabled = true, string $paginationPageParameterName = 'page', bool $clientItemsPerPage = false, string $itemsPerPageParameterName = 'itemsPerPage', OperationAwareFormatsProviderInterface $formatsProvider = null, bool $paginationClientEnabled = false, string $paginationClientEnabledParameterName = 'pagination', NormalizerInterface $legacyNormalizer = null, array $defaultContext = [])
    {
        $this->setFilterLocator($filterLocator, true);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->legacyNormalizer = $legacyNormalizer;
        $this->nameConverter = $nameConverter;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->apiKeys = $apiKeys;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->formatsProvider = $formatsProvider;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientEnabledParameterName = $paginationClientEnabledParameterName;

        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (2 === ($context['spec_version'] ?? 3) || ($context['api_gateway'] ?? false)) {
            return $this->legacyNormalizer->normalize($object, $format, $context);
        }

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
                if (null !== $this->formatsProvider) {
                    $responseFormats = $this->formatsProvider->getFormatsFromOperation($subresourceOperation['resource_class'], $operationName, OperationType::SUBRESOURCE);
                    $responseMimeTypes = $this->extractMimeTypes($responseFormats);
                }
                $pathOperation['summary'] = sprintf('Retrieves %s%s resource%s.', $subresourceOperation['collection'] ? 'the collection of ' : 'a ', $subresourceOperation['shortNames'][0], $subresourceOperation['collection'] ? 's' : '');
                $pathOperation['responses'] = [
                    '200' => $subresourceOperation['collection'] ? [
                        'description' => sprintf('%s collection response', $subresourceOperation['shortNames'][0]),
                        'content' => array_fill_keys($responseMimeTypes ?? $mimeTypes, ['schema' => ['type' => 'array', 'items' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]]),
                    ] : [
                        'description' => sprintf('%s resource response', $subresourceOperation['shortNames'][0]),
                        'content' => array_fill_keys($responseMimeTypes ?? $mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]),
                    ],
                    '404' => ['description' => 'Resource not found'],
                ];

                // Avoid duplicates parameters when there is a filter on a subresource identifier
                $parametersMemory = [];
                $pathOperation['parameters'] = [];

                foreach ($subresourceOperation['identifiers'] as list($identifier, , $hasIdentifier)) {
                    if (true === $hasIdentifier) {
                        $pathOperation['parameters'][] = ['name' => $identifier, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']];
                        $parametersMemory[] = $identifier;
                    }
                }

                if ($parameters = $this->getFiltersParameters($subresourceOperation['resource_class'], $operationName, $subResourceMetadata, $definitions, $serializerContext)) {
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
     * @return \ArrayObject
     */
    protected function updateGetOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $serializerContext = $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName);
        $responseDefinitionKey = $this->getDefinition($definitions, $resourceMetadata, $resourceClass, $serializerContext);

        if (OperationType::COLLECTION === $operationType) {
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceShortName);

            $pathOperation['responses'] ?? $pathOperation['responses'] = [
                '200' => [
                    'description' => sprintf('%s collection response', $resourceShortName),
                    'content' => array_fill_keys($mimeTypes, [
                        'schema' => [
                            'type' => 'array',
                            'items' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)],
                        ],
                    ]),
                ],
            ];

            $pathOperation['parameters'] ?? $pathOperation['parameters'] = $this->getFiltersParameters($resourceClass, $operationName, $resourceMetadata, $definitions, $serializerContext);

            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', true, true)) {
                $pathOperation['parameters'][] = $this->getPaginationParameters();

                if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                    $pathOperation['parameters'][] = $this->getItemsPerPageParameters();
                }
            }
            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->paginationClientEnabled, true)) {
                $pathOperation['parameters'][] = $this->getPaginationClientEnabledParameters();
            }

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
            ],
        ]];

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource response', $resourceShortName),
                'content' => array_fill_keys($mimeTypes, [
                    'schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)],
                ]),
            ],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    /**
     * @return \ArrayObject
     */
    protected function updatePostOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '201' => [
                'description' => sprintf('%s resource created', $resourceShortName),
                'content' => array_fill_keys($mimeTypes, [
                    'schema' => ['$ref' => sprintf('#/components/schemas/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                        $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName))),
                    ],
                ]),
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['requestBody'] ?? $pathOperation['requestBody'] = [
            'content' => array_fill_keys($mimeTypes, [
                'schema' => ['$ref' => sprintf('#/components/schemas/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName))),
                ],
            ]),
            'description' => sprintf('The new %s resource', $resourceShortName),
        ];

        return $pathOperation;
    }

    /**
     * @return \ArrayObject
     */
    protected function updatePutOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [
            [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ],
        ];

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource updated', $resourceShortName),
                'content' => array_fill_keys($mimeTypes, [
                    'schema' => ['$ref' => sprintf('#/components/schemas/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                        $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName))),
                    ],
                ]),
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['requestBody'] ?? $pathOperation['requestBody'] = [
            'content' => array_fill_keys($mimeTypes, [
                'schema' => ['$ref' => sprintf('#/components/schemas/%s', $this->getDefinition($definitions, $resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName))),
                ],
            ]),
            'description' => sprintf('The updated %s resource', $resourceShortName),
        ];

        return $pathOperation;
    }

    protected function updateDeleteOperation(\ArrayObject $pathOperation, string $resourceShortName): \ArrayObject
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '204' => ['description' => sprintf('%s resource deleted', $resourceShortName)],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
            ],
        ]];

        return $pathOperation;
    }

    /**
     * Computes the OpenAPI documentation.
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths, array $context): array
    {
        $doc = [
            'openapi' => self::OPENAPI_VERSION,
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
            'servers' => [
                ['url' => $context['base_url'] ?? '/'],
            ],
        ];

        if ('' !== $description = $documentation->getDescription()) {
            $doc['info']['description'] = $description;
        }

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

        if ($securityDefinitions && $security) {
            $doc['security'] = $security;
        }

        if (\count($definitions) + \count($securityDefinitions)) {
            $doc['components'] = [];
            $doc['components']['schemas'] = \count($definitions) ? $definitions : null;
            $doc['components']['securitySchemes'] = \count($securityDefinitions) ? $securityDefinitions : null;
            $doc['components'] = array_filter($doc['components']);
        }

        return $doc;
    }

    /**
     * Gets OpenAPI parameters corresponding to enabled filters.
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
                    'schema' => $this->getType($data['type'], $data['is_collection'] ?? false, null, null, $definitions, $serializerContext),
                ];

                if ('array' === $parameter['schema']['type'] ?? '') {
                    $parameter['style'] = \in_array($data['type'], [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_OBJECT], true) ? 'deepObject' : 'form';
                    $parameter['explode'] = true;
                }

                if (isset($data['swagger'])) {
                    $parameter = $data['swagger'] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationParameters(): array
    {
        $parameters = array_merge(parent::getPaginationParameters(), ['schema' => ['type' => 'integer']]);
        unset($parameters['type']);

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationClientEnabledParameters(): array
    {
        $parameters = array_merge(parent::getPaginationClientEnabledParameters(), ['schema' => ['type' => 'boolean']]);
        unset($parameters['type']);

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsPerPageParameters(): array
    {
        $parameters = array_merge(parent::getItemsPerPageParameters(), ['schema' => ['type' => 'integer']]);
        unset($parameters['type']);

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $type, bool $isCollection, string $className = null, bool $readableLink = null, \ArrayObject $definitions, array $serializerContext = null): array
    {
        $type = parent::getType($type, $isCollection, $className, $readableLink, $definitions, $serializerContext);

        if ($type['$ref'] ?? false) {
            $type['$ref'] = str_replace('#/definitions/', '#/components/schemas/', $type['$ref']);
        }

        return $type;
    }
}
