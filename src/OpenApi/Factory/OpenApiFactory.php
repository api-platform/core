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

namespace ApiPlatform\OpenApi\Factory;

use ApiPlatform\Api\FilterLocatorTrait;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface as LegacyPropertyNameCollectionFactoryInterface;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\PathResolver\OperationPathResolverInterface;
use ApiPlatform\State\Pagination\PaginationOptions;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates an Open API v3 specification.
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    use FilterLocatorTrait;

    public const BASE_URL = 'base_url';
    public const OPENAPI_DEFINITION_NAME = 'openapi_definition_name';

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    /**
     * @var LegacyPropertyNameCollectionFactoryInterface|PropertyNameCollectionFactoryInterface
     */
    private $propertyNameCollectionFactory;
    /**
     * @var LegacyPropertyMetadataFactoryInterface|PropertyMetadataFactoryInterface
     */
    private $propertyMetadataFactory;
    private $operationPathResolver;
    private $formats;
    private $jsonSchemaFactory;
    private $jsonSchemaTypeFactory;
    private $openApiOptions;
    private $paginationOptions;
    private $router;
    private $routeCollection;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, SchemaFactoryInterface $jsonSchemaFactory, TypeFactoryInterface $jsonSchemaTypeFactory, OperationPathResolverInterface $operationPathResolver, ContainerInterface $filterLocator, array $formats = [], Options $openApiOptions = null, PaginationOptions $paginationOptions = null, RouterInterface $router = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->jsonSchemaFactory = $jsonSchemaFactory;
        $this->jsonSchemaTypeFactory = $jsonSchemaTypeFactory;
        $this->formats = $formats;
        $this->setFilterLocator($filterLocator, true);
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->openApiOptions = $openApiOptions ?: new Options('API Platform');
        $this->paginationOptions = $paginationOptions ?: new PaginationOptions();
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        $baseUrl = $context[self::BASE_URL] ?? '/';
        $contact = null === $this->openApiOptions->getContactUrl() || null === $this->openApiOptions->getContactEmail() ? null : new Model\Contact($this->openApiOptions->getContactName(), $this->openApiOptions->getContactUrl(), $this->openApiOptions->getContactEmail());
        $license = null === $this->openApiOptions->getLicenseName() ? null : new Model\License($this->openApiOptions->getLicenseName(), $this->openApiOptions->getLicenseUrl());
        $info = new Model\Info($this->openApiOptions->getTitle(), $this->openApiOptions->getVersion(), trim($this->openApiOptions->getDescription()), $this->openApiOptions->getTermsOfService(), $contact, $license);
        $servers = '/' === $baseUrl || '' === $baseUrl ? [new Model\Server('/')] : [new Model\Server($baseUrl)];
        $paths = new Model\Paths();
        $schemas = new \ArrayObject();

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadataCollection as $resourceMetadata) {
                $this->collectPaths($resourceMetadata, $resourceMetadataCollection, $paths, $schemas);
            }
        }

        $securitySchemes = $this->getSecuritySchemes();
        $securityRequirements = [];

        foreach (array_keys($securitySchemes) as $key) {
            $securityRequirements[] = [$key => []];
        }

        return new OpenApi(
            $info,
            $servers,
            $paths,
            new Model\Components(
                $schemas,
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject($securitySchemes)
            ),
            $securityRequirements
        );
    }

    private function collectPaths(ApiResource $resource, ResourceMetadataCollection $resourceMetadataCollection, Model\Paths $paths, \ArrayObject $schemas): void
    {
        if (0 === $resource->getOperations()->count()) {
            return;
        }

        foreach ($resource->getOperations() as $operationName => $operation) {
            $resourceShortName = $operation->getShortName();
            // No path to return
            if (null === $operation->getUriTemplate() && null === $operation->getRouteName()) {
                continue;
            }

            // Operation ignored from OpenApi
            if ($operation instanceof HttpOperation && false === $operation->getOpenapi()) {
                continue;
            }

            $uriVariables = $operation->getUriVariables();
            $resourceClass = $operation->getClass() ?? $resource->getClass();
            $routeName = $operation->getRouteName() ?? $operation->getName();

            if (!$this->routeCollection && $this->router) {
                $this->routeCollection = $this->router->getRouteCollection();
            }

            $path = $this->getPath($routeName && $this->routeCollection ? $this->routeCollection->get($routeName)->getPath() : ($operation->getRoutePrefix() ?? '').$operation->getUriTemplate());
            $method = $operation->getMethod() ?? HttpOperation::METHOD_GET;

            if (!\in_array($method, Model\PathItem::$methods, true)) {
                continue;
            }

            [$requestMimeTypes, $responseMimeTypes] = $this->getMimeTypes($operation);

            $operationId = $operation->getOpenapiContext()['operationId'] ?? $operationName;

            if ($path) {
                $pathItem = $paths->getPath($path) ?: new Model\PathItem();
            } else {
                $pathItem = new Model\PathItem();
            }

            $forceSchemaCollection = $operation instanceof CollectionOperationInterface && 'GET' === $method ? true : false;
            $schema = new Schema('openapi');
            $schema->setDefinitions($schemas);

            $operationOutputSchemas = [];

            foreach ($responseMimeTypes as $operationFormat) {
                $operationOutputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_OUTPUT, $operation, $schema, null, $forceSchemaCollection);
                $operationOutputSchemas[$operationFormat] = $operationOutputSchema;
                $this->appendSchemaDefinitions($schemas, $operationOutputSchema->getDefinitions());
            }

            $parameters = [];
            $responses = [];

            if ($operation->getOpenapiContext()['parameters'] ?? false) {
                foreach ($operation->getOpenapiContext()['parameters'] as $parameter) {
                    $parameters[] = new Model\Parameter($parameter['name'], $parameter['in'], $parameter['description'] ?? '', $parameter['required'] ?? false, $parameter['deprecated'] ?? false, $parameter['allowEmptyValue'] ?? false, $parameter['schema'] ?? [], $parameter['style'] ?? null, $parameter['explode'] ?? false, $parameter['allowReserved '] ?? false, $parameter['example'] ?? null, isset($parameter['examples']) ? new \ArrayObject($parameter['examples']) : null, isset($parameter['content']) ? new \ArrayObject($parameter['content']) : null);
                }
            }

            // Set up parameters
            foreach ($uriVariables ?? [] as $parameterName => $uriVariable) {
                if ($uriVariable->getExpandedValue() ?? false) {
                    continue;
                }

                $parameter = new Model\Parameter($parameterName, 'path', (new \ReflectionClass($uriVariable->getFromClass()))->getShortName().' identifier', true, false, false, ['type' => 'string']);
                if ($this->hasParameter($parameter, $parameters)) {
                    continue;
                }

                $parameters[] = $parameter;
            }

            if ($operation instanceof CollectionOperationInterface && HttpOperation::METHOD_POST !== $method) {
                /* @phpstan-ignore-next-line phpstan looses the Operation type */
                foreach (array_merge($this->getPaginationParameters($operation), $this->getFiltersParameters($operation)) as $parameter) {
                    if ($this->hasParameter($parameter, $parameters)) {
                        continue;
                    }

                    $parameters[] = $parameter;
                }
            }

            // Create responses
            switch ($method) {
                case HttpOperation::METHOD_GET:
                    $successStatus = (string) $operation->getStatus() ?: 200;
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $responses[$successStatus] = new Model\Response(sprintf('%s %s', $resourceShortName, $operation instanceof CollectionOperationInterface ? 'collection' : 'resource'), $responseContent);
                    break;
                case HttpOperation::METHOD_POST:
                    $responseLinks = $this->getLinks($resourceMetadataCollection, $operation);
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $successStatus = (string) $operation->getStatus() ?: 201;
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource created', $resourceShortName), $responseContent, null, $responseLinks);
                    $responses['400'] = new Model\Response('Invalid input');
                    $responses['422'] = new Model\Response('Unprocessable entity');
                    break;
                case HttpOperation::METHOD_PATCH:
                case HttpOperation::METHOD_PUT:
                    $responseLinks = $this->getLinks($resourceMetadataCollection, $operation);
                    $successStatus = (string) $operation->getStatus() ?: 200;
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource updated', $resourceShortName), $responseContent, null, $responseLinks);
                    $responses['400'] = new Model\Response('Invalid input');
                    $responses['422'] = new Model\Response('Unprocessable entity');
                    break;
                case HttpOperation::METHOD_DELETE:
                    $successStatus = (string) $operation->getStatus() ?: 204;
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource deleted', $resourceShortName));
                    break;
            }

            if (!$operation instanceof CollectionOperationInterface && HttpOperation::METHOD_POST !== $operation->getMethod()) {
                $responses['404'] = new Model\Response('Resource not found');
            }

            if (!$responses) {
                $responses['default'] = new Model\Response('Unexpected error');
            }

            if ($contextResponses = $operation->getOpenapiContext()['responses'] ?? false) {
                foreach ($contextResponses as $statusCode => $contextResponse) {
                    $responses[$statusCode] = new Model\Response($contextResponse['description'] ?? '', isset($contextResponse['content']) ? new \ArrayObject($contextResponse['content']) : null, isset($contextResponse['headers']) ? new \ArrayObject($contextResponse['headers']) : null, isset($contextResponse['links']) ? new \ArrayObject($contextResponse['links']) : null);
                }
            }

            $requestBody = null;
            if ($contextRequestBody = $operation->getOpenapiContext()['requestBody'] ?? false) {
                $requestBody = new Model\RequestBody($contextRequestBody['description'] ?? '', new \ArrayObject($contextRequestBody['content']), $contextRequestBody['required'] ?? false);
            } elseif (\in_array($method, [HttpOperation::METHOD_PATCH, HttpOperation::METHOD_PUT, HttpOperation::METHOD_POST], true)) {
                $operationInputSchemas = [];
                foreach ($requestMimeTypes as $operationFormat) {
                    $operationInputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_INPUT, $operation, $schema, null, $forceSchemaCollection);
                    $operationInputSchemas[$operationFormat] = $operationInputSchema;
                    $this->appendSchemaDefinitions($schemas, $operationInputSchema->getDefinitions());
                }

                $requestBody = new Model\RequestBody(sprintf('The %s %s resource', HttpOperation::METHOD_POST === $method ? 'new' : 'updated', $resourceShortName), $this->buildContent($requestMimeTypes, $operationInputSchemas), true);
            }

            $pathItem = $pathItem->{'with'.ucfirst($method)}(new Model\Operation(
                $operationId,
                $operation->getOpenapiContext()['tags'] ?? [$operation->getShortName() ?: $resourceShortName],
                $responses,
                $operation->getOpenapiContext()['summary'] ?? $this->getPathDescription($resourceShortName, $method, $operation instanceof CollectionOperationInterface),
                $operation->getOpenapiContext()['description'] ?? $this->getPathDescription($resourceShortName, $method, $operation instanceof CollectionOperationInterface),
                isset($operation->getOpenapiContext()['externalDocs']) ? new ExternalDocumentation($operation->getOpenapiContext()['externalDocs']['description'] ?? null, $operation->getOpenapiContext()['externalDocs']['url']) : null,
                $parameters,
                $requestBody,
                isset($operation->getOpenapiContext()['callbacks']) ? new \ArrayObject($operation->getOpenapiContext()['callbacks']) : null,
                $operation->getOpenapiContext()['deprecated'] ?? (bool) $operation->getDeprecationReason(),
                $operation->getOpenapiContext()['security'] ?? null,
                $operation->getOpenapiContext()['servers'] ?? null,
                array_filter($operation->getOpenapiContext() ?? [], static function ($item) {
                    return preg_match('/^x-.*$/i', $item);
                }, \ARRAY_FILTER_USE_KEY)
            ));

            $paths->addPath($path, $pathItem);
        }
    }

    /**
     * @return \ArrayObject<Model\MediaType>
     */
    private function buildContent(array $responseMimeTypes, array $operationSchemas): \ArrayObject
    {
        /** @var \ArrayObject<Model\MediaType> */
        $content = new \ArrayObject();

        foreach ($responseMimeTypes as $mimeType => $format) {
            $content[$mimeType] = new Model\MediaType(new \ArrayObject($operationSchemas[$format]->getArrayCopy(false)));
        }

        return $content;
    }

    private function getMimeTypes(HttpOperation $operation): array
    {
        $requestFormats = $operation->getInputFormats() ?: [];
        $responseFormats = $operation->getOutputFormats() ?: [];

        $requestMimeTypes = $this->flattenMimeTypes($requestFormats);
        $responseMimeTypes = $this->flattenMimeTypes($responseFormats);

        return [$requestMimeTypes, $responseMimeTypes];
    }

    private function flattenMimeTypes(array $responseFormats): array
    {
        $responseMimeTypes = [];
        foreach ($responseFormats as $responseFormat => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $responseMimeTypes[$mimeType] = $responseFormat;
            }
        }

        return $responseMimeTypes;
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     */
    private function getPath(string $path): string
    {
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return 0 === strpos($path, '/') ? $path : '/'.$path;
    }

    private function getPathDescription(string $resourceShortName, string $method, bool $isCollection): string
    {
        switch ($method) {
            case 'GET':
                $pathSummary = $isCollection ? 'Retrieves the collection of %s resources.' : 'Retrieves a %s resource.';
                break;
            case 'POST':
                $pathSummary = 'Creates a %s resource.';
                break;
            case 'PATCH':
                $pathSummary = 'Updates the %s resource.';
                break;
            case 'PUT':
                $pathSummary = 'Replaces the %s resource.';
                break;
            case 'DELETE':
                $pathSummary = 'Removes the %s resource.';
                break;
            default:
                return $resourceShortName;
        }

        return sprintf($pathSummary, $resourceShortName);
    }

    /**
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject.
     *
     * @return \ArrayObject<Model\Link>
     */
    private function getLinks(ResourceMetadataCollection $resourceMetadataCollection, HttpOperation $currentOperation): \ArrayObject
    {
        /** @var \ArrayObject<Model\Link> */
        $links = new \ArrayObject();

        // Only compute get links for now
        foreach ($resourceMetadataCollection as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                $parameters = [];
                $method = $operation instanceof HttpOperation ? $operation->getMethod() : HttpOperation::METHOD_GET;
                if (
                    $operationName === $operation->getName() ||
                    isset($links[$operationName]) ||
                    $operation instanceof CollectionOperationInterface ||
                    HttpOperation::METHOD_GET !== ($method ?? null)
                ) {
                    continue;
                }

                // Operation ignored from OpenApi
                if ($operation instanceof HttpOperation && false === $operation->getOpenapi()) {
                    continue;
                }

                $operationUriVariables = $operation->getUriVariables();
                foreach ($currentOperation->getUriVariables() ?? [] as $parameterName => $uriVariableDefinition) {
                    if (!isset($operationUriVariables[$parameterName])) {
                        continue;
                    }

                    if ($operationUriVariables[$parameterName]->getIdentifiers() === $uriVariableDefinition->getIdentifiers() && $operationUriVariables[$parameterName]->getFromClass() === $uriVariableDefinition->getFromClass()) {
                        $parameters[$parameterName] = '$request.path.'.$uriVariableDefinition->getIdentifiers()[0];
                    }
                }

                foreach ($operationUriVariables ?? [] as $parameterName => $uriVariableDefinition) {
                    if (isset($parameters[$parameterName])) {
                        continue;
                    }

                    if (($uriVariableDefinition->getFromClass() ?? null) === $currentOperation->getClass()) {
                        $parameters[$parameterName] = '$response.body#/'.$uriVariableDefinition->getIdentifiers()[0];
                    }
                }

                $links[$operationName] = new Model\Link(
                    $operationName,
                    new \ArrayObject($parameters),
                    null,
                    $operation->getDescription() ?? ''
                );
            }
        }

        return $links;
    }

    /**
     * Gets parameters corresponding to enabled filters.
     */
    private function getFiltersParameters(HttpOperation $operation): array
    {
        $parameters = [];

        $resourceFilters = $operation->getFilters();
        foreach ($resourceFilters ?? [] as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($operation->getClass()) as $name => $data) {
                $schema = $data['schema'] ?? (\in_array($data['type'], Type::$builtinTypes, true) ? $this->jsonSchemaTypeFactory->getType(new Type($data['type'], false, null, $data['is_collection'] ?? false)) : ['type' => 'string']);

                $parameters[] = new Model\Parameter(
                    $name,
                    'query',
                    $data['description'] ?? '',
                    $data['required'] ?? false,
                    $data['openapi']['deprecated'] ?? false,
                    $data['openapi']['allowEmptyValue'] ?? true,
                    $schema,
                    'array' === $schema['type'] && \in_array(
                        $data['type'],
                        [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_OBJECT],
                        true
                    ) ? 'deepObject' : 'form',
                    $data['openapi']['explode'] ?? ('array' === $schema['type']),
                    $data['openapi']['allowReserved'] ?? false,
                    $data['openapi']['example'] ?? null,
                    isset($data['openapi']['examples']
                    ) ? new \ArrayObject($data['openapi']['examples']) : null
                );
            }
        }

        return $parameters;
    }

    private function getPaginationParameters(Operation $operation): array
    {
        if (!$this->paginationOptions->isPaginationEnabled()) {
            return [];
        }

        $parameters = [];

        if ($operation->getPaginationEnabled() ?? $this->paginationOptions->isPaginationEnabled()) {
            $parameters[] = new Model\Parameter($this->paginationOptions->getPaginationPageParameterName(), 'query', 'The collection page number', false, false, true, ['type' => 'integer', 'default' => 1]);

            if ($operation->getPaginationClientItemsPerPage() ?? $this->paginationOptions->getClientItemsPerPage()) {
                $schema = [
                    'type' => 'integer',
                    'default' => $operation->getPaginationItemsPerPage() ?? $this->paginationOptions->getItemsPerPage(),
                    'minimum' => 0,
                ];

                if (null !== $maxItemsPerPage = ($operation->getPaginationMaximumItemsPerPage() ?? $this->paginationOptions->getMaximumItemsPerPage())) {
                    $schema['maximum'] = $maxItemsPerPage;
                }

                $parameters[] = new Model\Parameter($this->paginationOptions->getItemsPerPageParameterName(), 'query', 'The number of items per page', false, false, true, $schema);
            }
        }

        if ($operation->getPaginationClientEnabled() ?? $this->paginationOptions->isPaginationClientEnabled()) {
            $parameters[] = new Model\Parameter($this->paginationOptions->getPaginationClientEnabledParameterName(), 'query', 'Enable or disable pagination', false, false, true, ['type' => 'boolean']);
        }

        return $parameters;
    }

    private function getOauthSecurityScheme(): Model\SecurityScheme
    {
        $oauthFlow = new Model\OAuthFlow($this->openApiOptions->getOAuthAuthorizationUrl(), $this->openApiOptions->getOAuthTokenUrl(), $this->openApiOptions->getOAuthRefreshUrl(), new \ArrayObject($this->openApiOptions->getOAuthScopes()));
        $description = sprintf(
            'OAuth 2.0 %s Grant',
            strtolower(preg_replace('/[A-Z]/', ' \\0', lcfirst($this->openApiOptions->getOAuthFlow())))
        );
        $implicit = $password = $clientCredentials = $authorizationCode = null;

        switch ($this->openApiOptions->getOAuthFlow()) {
            case 'implicit':
                $implicit = $oauthFlow;
                break;
            case 'password':
                $password = $oauthFlow;
                break;
            case 'application':
            case 'clientCredentials':
                $clientCredentials = $oauthFlow;
                break;
            case 'accessCode':
            case 'authorizationCode':
                $authorizationCode = $oauthFlow;
                break;
            default:
                throw new \LogicException('OAuth flow must be one of: implicit, password, clientCredentials, authorizationCode');
        }

        return new Model\SecurityScheme($this->openApiOptions->getOAuthType(), $description, null, null, null, null, new Model\OAuthFlows($implicit, $password, $clientCredentials, $authorizationCode), null);
    }

    private function getSecuritySchemes(): array
    {
        $securitySchemes = [];

        if ($this->openApiOptions->getOAuthEnabled()) {
            $securitySchemes['oauth'] = $this->getOauthSecurityScheme();
        }

        foreach ($this->openApiOptions->getApiKeys() as $key => $apiKey) {
            $description = sprintf('Value for the %s %s parameter.', $apiKey['name'], $apiKey['type']);
            $securitySchemes[$key] = new Model\SecurityScheme('apiKey', $description, $apiKey['name'], $apiKey['type']);
        }

        return $securitySchemes;
    }

    private function appendSchemaDefinitions(\ArrayObject $schemas, \ArrayObject $definitions): void
    {
        foreach ($definitions as $key => $value) {
            $schemas[$key] = $value;
        }
    }

    /**
     * @param Model\Parameter[] $parameters
     */
    private function hasParameter(Model\Parameter $parameter, array $parameters): bool
    {
        foreach ($parameters as $existingParameter) {
            if ($existingParameter->getName() === $parameter->getName() && $existingParameter->getIn() === $parameter->getIn()) {
                return true;
            }
        }

        return false;
    }
}
