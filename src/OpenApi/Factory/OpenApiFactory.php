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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Contact;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\License;
use ApiPlatform\OpenApi\Model\Link;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\OAuthFlow;
use ApiPlatform\OpenApi\Model\OAuthFlows;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\NormalizeOperationNameTrait;
use ApiPlatform\State\ApiResource\Error as ApiResourceError;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\Util\StateOptionsTrait;
use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Generates an Open API v3 specification.
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    use NormalizeOperationNameTrait;
    use StateOptionsTrait;
    use TypeFactoryTrait;

    public const BASE_URL = 'base_url';
    public const API_PLATFORM_TAG = 'x-apiplatform-tag';
    public const OVERRIDE_OPENAPI_RESPONSES = 'open_api_override_responses';
    private readonly Options $openApiOptions;
    private readonly PaginationOptions $paginationOptions;
    private ?RouteCollection $routeCollection = null;
    private ?ContainerInterface $filterLocator = null;
    /**
     * @var array<string|class-string, ErrorResource>
     */
    private array $localErrorResourceCache = [];

    /**
     * @param array<string, string[]> $formats
     */
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly SchemaFactoryInterface $jsonSchemaFactory,
        ?ContainerInterface $filterLocator = null,
        private readonly array $formats = [],
        ?Options $openApiOptions = null,
        ?PaginationOptions $paginationOptions = null,
        private readonly ?RouterInterface $router = null,
        private readonly array $errorFormats = [],
    ) {
        $this->filterLocator = $filterLocator;
        $this->openApiOptions = $openApiOptions ?: new Options('API Platform');
        $this->paginationOptions = $paginationOptions ?: new PaginationOptions();
    }

    /**
     * {@inheritdoc}
     *
     * You can filter openapi operations with the `x-apiplatform-tag` on an OpenApi Operation using the `filter_tags`.
     *
     * @param array{base_url?: string, filter_tags?: string[]}&array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $baseUrl = $context[self::BASE_URL] ?? '/';
        $contact = null === $this->openApiOptions->getContactUrl() || null === $this->openApiOptions->getContactEmail() ? null : new Contact($this->openApiOptions->getContactName(), $this->openApiOptions->getContactUrl(), $this->openApiOptions->getContactEmail());
        $license = null === $this->openApiOptions->getLicenseName() ? null : new License($this->openApiOptions->getLicenseName(), $this->openApiOptions->getLicenseUrl(), $this->openApiOptions->getLicenseIdentifier());
        $info = new Info($this->openApiOptions->getTitle(), $this->openApiOptions->getVersion(), trim($this->openApiOptions->getDescription()), $this->openApiOptions->getTermsOfService(), $contact, $license);
        $servers = '/' === $baseUrl || '' === $baseUrl ? [new Server('/')] : [new Server($baseUrl)];
        $paths = new Paths();
        $schemas = new \ArrayObject();
        $webhooks = new \ArrayObject();
        $tags = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                $this->collectPaths($resourceMetadata, $resourceMetadataCollection, $paths, $schemas, $webhooks, $tags, $context);
            }
        }

        $securitySchemes = $this->getSecuritySchemes();
        $securityRequirements = [];

        foreach (array_keys($securitySchemes) as $key) {
            $securityRequirements[] = [$key => []];
        }

        $globalTags = $this->openApiOptions->getTags() ?: array_values($tags) ?: [];

        return new OpenApi(
            $info,
            $servers,
            $paths,
            new Components(
                $schemas,
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject($securitySchemes)
            ),
            $securityRequirements,
            $globalTags,
            null,
            null,
            $webhooks
        );
    }

    private function collectPaths(ApiResource $resource, ResourceMetadataCollection $resourceMetadataCollection, Paths $paths, \ArrayObject $schemas, \ArrayObject $webhooks, array &$tags, array $context = []): void
    {
        if (0 === $resource->getOperations()->count()) {
            return;
        }

        $defaultError = $this->getErrorResource($this->openApiOptions->getErrorResourceClass() ?? ApiResourceError::class);
        $defaultValidationError = $this->getErrorResource($this->openApiOptions->getValidationErrorResourceClass() ?? ValidationException::class, 422, 'Unprocessable entity');

        // This filters on our extension x-apiplatform-tag as the openapi operation tag is used for ordering operations
        $filteredTags = $context['filter_tags'] ?? [];
        if (!\is_array($filteredTags)) {
            $filteredTags = [$filteredTags];
        }

        foreach ($resource->getOperations() as $operationName => $operation) {
            $resourceShortName = $operation->getShortName() ?? $operation;
            // No path to return
            if (null === $operation->getUriTemplate() && null === $operation->getRouteName()) {
                continue;
            }

            $openapiAttribute = $operation->getOpenapi();

            // Operation ignored from OpenApi
            if (false === $openapiAttribute) {
                continue;
            }

            // See https://github.com/api-platform/core/issues/6993 we would like to allow only `false` but as we typed `bool` we have this check
            $operationTag = !\is_object($openapiAttribute) ? [] : ($openapiAttribute->getExtensionProperties()[self::API_PLATFORM_TAG] ?? []);
            if (!\is_array($operationTag)) {
                $operationTag = [$operationTag];
            }

            if ($filteredTags && $filteredTags !== array_intersect($filteredTags, $operationTag)) {
                continue;
            }

            $resourceClass = $operation->getClass() ?? $resource->getClass();
            $routeName = $operation->getRouteName() ?? $operation->getName();

            if (!$this->routeCollection && $this->router) {
                $this->routeCollection = $this->router->getRouteCollection();
            }

            if ($this->routeCollection && $routeName && $route = $this->routeCollection->get($routeName)) {
                $path = $route->getPath();
            } else {
                $path = rtrim($operation->getRoutePrefix() ?? '', '/').'/'.ltrim($operation->getUriTemplate() ?? '', '/');
            }

            $path = $this->getPath($path);
            $method = $operation->getMethod();

            if (!\in_array($method, PathItem::$methods, true)) {
                continue;
            }

            $pathItem = null;

            if ($openapiAttribute instanceof Webhook) {
                $pathItem = $openapiAttribute->getPathItem() ?: new PathItem();
                $openapiOperation = $pathItem->{'get'.ucfirst(strtolower($method))}() ?: new Operation();
            } elseif (!\is_object($openapiAttribute)) {
                $openapiOperation = new Operation();
            } else {
                $openapiOperation = $openapiAttribute;
            }

            // Complete with defaults
            $openapiOperation = new Operation(
                operationId: null !== $openapiOperation->getOperationId() ? $openapiOperation->getOperationId() : $this->normalizeOperationName($operationName),
                tags: null !== $openapiOperation->getTags() ? $openapiOperation->getTags() : [$operation->getShortName() ?: $resourceShortName],
                responses: null !== $openapiOperation->getResponses() ? $openapiOperation->getResponses() : [],
                summary: null !== $openapiOperation->getSummary() ? $openapiOperation->getSummary() : $this->getPathDescription($resourceShortName, $method, $operation instanceof CollectionOperationInterface),
                description: null !== $openapiOperation->getDescription() ? $openapiOperation->getDescription() : $this->getPathDescription($resourceShortName, $method, $operation instanceof CollectionOperationInterface),
                externalDocs: $openapiOperation->getExternalDocs(),
                parameters: null !== $openapiOperation->getParameters() ? $openapiOperation->getParameters() : [],
                requestBody: $openapiOperation->getRequestBody(),
                callbacks: $openapiOperation->getCallbacks(),
                deprecated: null !== $openapiOperation->getDeprecated() ? $openapiOperation->getDeprecated() : ($operation->getDeprecationReason() ? true : null),
                security: null !== $openapiOperation->getSecurity() ? $openapiOperation->getSecurity() : null,
                servers: null !== $openapiOperation->getServers() ? $openapiOperation->getServers() : null,
                extensionProperties: $openapiOperation->getExtensionProperties(),
            );

            foreach ($openapiOperation->getTags() as $v) {
                $tags[$v] = new Tag(name: $v, description: $resource->getDescription() ?? "Resource '$v' operations.");
            }

            [$requestMimeTypes, $responseMimeTypes] = $this->getMimeTypes($operation);

            if (null === $pathItem) {
                $pathItem = $paths->getPath($path) ?? new PathItem();
            }

            $forceSchemaCollection = $operation instanceof CollectionOperationInterface && 'GET' === $method;
            $schema = new Schema('openapi');
            $schema->setDefinitions($schemas);

            $operationOutputSchemas = [];

            foreach ($responseMimeTypes as $operationFormat) {
                $operationOutputSchema = null;
                // Having JSONSchema for non-json schema makes no sense
                if (str_starts_with($operationFormat, 'json')) {
                    $operationOutputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_OUTPUT, $operation, $schema, null, $forceSchemaCollection);
                    $this->appendSchemaDefinitions($schemas, $operationOutputSchema->getDefinitions());
                }

                $operationOutputSchemas[$operationFormat] = $operationOutputSchema;
            }

            // Set up parameters
            $openapiParameters = $openapiOperation->getParameters();
            foreach ($operation->getUriVariables() ?? [] as $parameterName => $uriVariable) {
                if ($uriVariable->getExpandedValue() ?? false) {
                    continue;
                }

                $parameter = new Parameter(
                    $parameterName,
                    'path',
                    $uriVariable->getDescription() ?? "$resourceShortName identifier",
                    $uriVariable->getRequired() ?? true,
                    false,
                    null,
                    $uriVariable->getSchema() ?? ['type' => 'string'],
                );

                if ($linkParameter = $uriVariable->getOpenApi()) {
                    $parameter = $this->mergeParameter($parameter, $linkParameter);
                }

                if ([$i, $operationParameter] = $this->hasParameter($openapiOperation, $parameter)) {
                    $openapiParameters[$i] = $this->mergeParameter($parameter, $operationParameter);
                    continue;
                }

                $openapiParameters[] = $parameter;
            }

            $openapiOperation = $openapiOperation->withParameters($openapiParameters);

            if ($operation instanceof CollectionOperationInterface && 'POST' !== $method) {
                foreach (array_merge($this->getPaginationParameters($operation), $this->getFiltersParameters($operation)) as $parameter) {
                    if ($operationParameter = $this->hasParameter($openapiOperation, $parameter)) {
                        continue;
                    }

                    $openapiOperation = $openapiOperation->withParameter($parameter);
                }
            }

            $entityClass = $this->getStateOptionsClass($operation, $operation->getClass());
            $openapiParameters = $openapiOperation->getParameters();
            foreach ($operation->getParameters() ?? [] as $key => $p) {
                if (false === $p->getOpenApi()) {
                    continue;
                }

                if (($f = $p->getFilter()) && \is_string($f) && $this->filterLocator && $this->filterLocator->has($f)) {
                    $filter = $this->filterLocator->get($f);

                    if ($d = $filter->getDescription($entityClass)) {
                        foreach ($d as $name => $description) {
                            if ($prop = $p->getProperty()) {
                                $name = str_replace($prop, $key, $name);
                            }

                            $openapiParameters[] = $this->getFilterParameter($name, $description, $operation->getShortName(), $f);
                        }

                        continue;
                    }
                }

                $in = $p instanceof HeaderParameterInterface ? 'header' : 'query';
                $defaultParameter = new Parameter(
                    $key,
                    $in,
                    $p->getDescription() ?? "$resourceShortName $key",
                    $p->getRequired() ?? false,
                    false,
                    null,
                    $p->getSchema() ?? ['type' => 'string'],
                );

                $linkParameter = $p->getOpenApi();
                if (null === $linkParameter) {
                    if ([$i, $operationParameter] = $this->hasParameter($openapiOperation, $defaultParameter)) {
                        $openapiParameters[$i] = $this->mergeParameter($defaultParameter, $operationParameter);
                    } else {
                        $openapiParameters[] = $defaultParameter;
                    }

                    continue;
                }

                if (\is_array($linkParameter)) {
                    foreach ($linkParameter as $lp) {
                        $parameter = $this->mergeParameter($defaultParameter, $lp);
                        if ([$i, $operationParameter] = $this->hasParameter($openapiOperation, $parameter)) {
                            $openapiParameters[$i] = $this->mergeParameter($parameter, $operationParameter);
                            continue;
                        }

                        $openapiParameters[] = $parameter;
                    }
                    continue;
                }

                $parameter = $this->mergeParameter($defaultParameter, $linkParameter);
                if ([$i, $operationParameter] = $this->hasParameter($openapiOperation, $parameter)) {
                    $openapiParameters[$i] = $this->mergeParameter($parameter, $operationParameter);
                    continue;
                }
                $openapiParameters[] = $parameter;
            }

            $openapiOperation = $openapiOperation->withParameters($openapiParameters);
            $existingResponses = $openapiOperation->getResponses() ?: [];
            $overrideResponses = $operation->getExtraProperties()[self::OVERRIDE_OPENAPI_RESPONSES] ?? $this->openApiOptions->getOverrideResponses();
            $errors = null;
            if (null !== ($errors = $operation->getErrors())) {
                /** @var array<class-string|string, Error> */
                $errorOperations = [];
                foreach ($errors as $error) {
                    $errorOperations[$error] = $this->getErrorResource($error);
                }

                $openapiOperation = $this->addOperationErrors($openapiOperation, $errorOperations, $resourceMetadataCollection, $schema, $schemas, $operation);
            }

            if ($overrideResponses || !$existingResponses) {
                // Create responses
                switch ($method) {
                    case 'GET':
                        $successStatus = (string) $operation->getStatus() ?: 200;
                        $openapiOperation = $this->buildOpenApiResponse($existingResponses, $successStatus, \sprintf('%s %s', $resourceShortName, $operation instanceof CollectionOperationInterface ? 'collection' : 'resource'), $openapiOperation, $operation, $responseMimeTypes, $operationOutputSchemas);
                        break;
                    case 'POST':
                        $successStatus = (string) $operation->getStatus() ?: 201;
                        $openapiOperation = $this->buildOpenApiResponse($existingResponses, $successStatus, \sprintf('%s resource created', $resourceShortName), $openapiOperation, $operation, $responseMimeTypes, $operationOutputSchemas, $resourceMetadataCollection);

                        if (null === $errors) {
                            $openapiOperation = $this->addOperationErrors($openapiOperation, [
                                $defaultError->withStatus(400)->withDescription('Invalid input'),
                                $defaultValidationError,
                            ], $resourceMetadataCollection, $schema, $schemas, $operation);
                        }
                        break;
                    case 'PATCH':
                    case 'PUT':
                        $successStatus = (string) $operation->getStatus() ?: 200;
                        $openapiOperation = $this->buildOpenApiResponse($existingResponses, $successStatus, \sprintf('%s resource updated', $resourceShortName), $openapiOperation, $operation, $responseMimeTypes, $operationOutputSchemas, $resourceMetadataCollection);

                        if (null === $errors) {
                            $openapiOperation = $this->addOperationErrors($openapiOperation, [
                                $defaultError->withStatus(400)->withDescription('Invalid input'),
                                $defaultValidationError,
                            ], $resourceMetadataCollection, $schema, $schemas, $operation);
                        }
                        break;
                    case 'DELETE':
                        $successStatus = (string) $operation->getStatus() ?: 204;
                        $openapiOperation = $this->buildOpenApiResponse($existingResponses, $successStatus, \sprintf('%s resource deleted', $resourceShortName), $openapiOperation);
                        break;
                }
            }

            if ($overrideResponses && !isset($existingResponses[403]) && $operation->getSecurity()) {
                $openapiOperation = $this->addOperationErrors($openapiOperation, [
                    $defaultError->withStatus(403)->withDescription('Forbidden'),
                ], $resourceMetadataCollection, $schema, $schemas, $operation);
            }

            if ($overrideResponses && !$operation instanceof CollectionOperationInterface && 'POST' !== $operation->getMethod() && !isset($existingResponses[404]) && null === $errors) {
                $openapiOperation = $this->addOperationErrors($openapiOperation, [
                    $defaultError->withStatus(404)->withDescription('Not found'),
                ], $resourceMetadataCollection, $schema, $schemas, $operation);
            }

            if (!$openapiOperation->getResponses()) {
                $openapiOperation = $openapiOperation->withResponse('default', new Response('Unexpected error'));
            }

            if (
                \in_array($method, ['PATCH', 'PUT', 'POST'], true)
                && !(false === ($input = $operation->getInput()) || (\is_array($input) && null === $input['class']))
            ) {
                $content = $openapiOperation->getRequestBody()?->getContent();
                if (null === $content) {
                    $operationInputSchemas = [];
                    foreach ($requestMimeTypes as $operationFormat) {
                        $operationInputSchema = null;
                        if (str_starts_with($operationFormat, 'json')) {
                            $operationInputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_INPUT, $operation, $schema, null, $forceSchemaCollection);
                            $this->appendSchemaDefinitions($schemas, $operationInputSchema->getDefinitions());
                        }

                        $operationInputSchemas[$operationFormat] = $operationInputSchema;
                    }
                    $content = $this->buildContent($requestMimeTypes, $operationInputSchemas);
                }

                $openapiOperation = $openapiOperation->withRequestBody(new RequestBody(
                    description: $openapiOperation->getRequestBody()?->getDescription() ?? \sprintf('The %s %s resource', 'POST' === $method ? 'new' : 'updated', $resourceShortName),
                    content: $content,
                    required: $openapiOperation->getRequestBody()?->getRequired() ?? true,
                ));
            }

            if ($openapiAttribute instanceof Webhook) {
                $webhooks[$openapiAttribute->getName()] = $pathItem->{'with'.ucfirst($method)}($openapiOperation);
                continue;
            }

            // We merge content types for errors, maybe that this logic could be applied to every resources at some point
            if ($operation instanceof Error && ($existingPathItem = $paths->getPath($path)) && ($existingOperation = $existingPathItem->getGet()) && ($currentResponse = $openapiOperation->getResponses()[200] ?? null)) {
                $errorResponse = $existingOperation->getResponses()[200];
                $currentResponseContent = $currentResponse->getContent();

                foreach ($errorResponse->getContent() as $mime => $content) {
                    $currentResponseContent[$mime] = $content;
                }

                $openapiOperation = $existingOperation->withResponse(200, $currentResponse->withContent($currentResponseContent));
            }

            $paths->addPath($path, $pathItem->{'with'.ucfirst($method)}($openapiOperation));
        }
    }

    private function buildOpenApiResponse(array $existingResponses, int|string $status, string $description, ?Operation $openapiOperation = null, ?HttpOperation $operation = null, ?array $responseMimeTypes = null, ?array $operationOutputSchemas = null, ?ResourceMetadataCollection $resourceMetadataCollection = null): Operation
    {
        $noOutput = \is_array($operation?->getOutput()) && null === $operation->getOutput()['class'];

        if (isset($existingResponses[$status])) {
            return $openapiOperation;
        }
        $responseLinks = $responseContent = null;
        if ($responseMimeTypes && $operationOutputSchemas && !$noOutput) {
            $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
        }
        if ($resourceMetadataCollection && $operation) {
            $responseLinks = $this->getLinks($resourceMetadataCollection, $operation);
        }

        return $openapiOperation->withResponse($status, new Response($description, $responseContent, null, $responseLinks));
    }

    /**
     * @param array<string, string> $responseMimeTypes
     * @param array<string, Schema> $operationSchemas
     *
     * @return \ArrayObject<MediaType>
     */
    private function buildContent(array $responseMimeTypes, array $operationSchemas): \ArrayObject
    {
        /** @var \ArrayObject<MediaType> $content */
        $content = new \ArrayObject();

        foreach ($responseMimeTypes as $mimeType => $format) {
            $content[$mimeType] = isset($operationSchemas[$format]) ? new MediaType(schema: new \ArrayObject($operationSchemas[$format]->getArrayCopy(false))) : new \ArrayObject();
        }

        return $content;
    }

    /**
     * @return array{array<string, string>, array<string, string>}
     */
    private function getMimeTypes(HttpOperation $operation): array
    {
        $requestFormats = $operation->getInputFormats() ?: [];
        $responseFormats = $operation->getOutputFormats() ?: [];

        $requestMimeTypes = $this->flattenMimeTypes($requestFormats);
        $responseMimeTypes = $this->flattenMimeTypes($responseFormats);

        return [$requestMimeTypes, $responseMimeTypes];
    }

    /**
     * @param array<string, string[]> $responseFormats
     *
     * @return array<string, string>
     */
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
        // Handle either API Platform's URI Template (rfc6570) or Symfony's route
        if (str_ends_with($path, '{._format}') || str_ends_with($path, '.{_format}')) {
            $path = substr($path, 0, -10);
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
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

        return \sprintf($pathSummary, $resourceShortName);
    }

    /**
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject.
     *
     * @return \ArrayObject<Link>
     */
    private function getLinks(ResourceMetadataCollection $resourceMetadataCollection, HttpOperation $currentOperation): \ArrayObject
    {
        /** @var \ArrayObject<Link> $links */
        $links = new \ArrayObject();

        // Only compute get links for now
        foreach ($resourceMetadataCollection as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                $parameters = [];
                $method = $operation->getMethod();
                if (
                    $operationName === $operation->getName()
                    || isset($links[$operationName])
                    || $operation instanceof CollectionOperationInterface
                    || 'GET' !== $method
                ) {
                    continue;
                }

                // Operation ignored from OpenApi
                if (false === $operation->getOpenapi() || $operation->getOpenapi() instanceof Webhook) {
                    continue;
                }

                $operationUriVariables = $operation->getUriVariables();
                foreach ($currentOperation->getUriVariables() ?? [] as $parameterName => $uriVariableDefinition) {
                    if (!isset($operationUriVariables[$parameterName])) {
                        continue;
                    }

                    if ($operationUriVariables[$parameterName]->getIdentifiers() === $uriVariableDefinition->getIdentifiers() && $operationUriVariables[$parameterName]->getFromClass() === $uriVariableDefinition->getFromClass()) {
                        $parameters[$parameterName] = '$request.path.'.($uriVariableDefinition->getIdentifiers()[0] ?? 'id');
                    }
                }

                foreach ($operationUriVariables ?? [] as $parameterName => $uriVariableDefinition) {
                    if (isset($parameters[$parameterName])) {
                        continue;
                    }

                    if ($uriVariableDefinition->getFromClass() === $currentOperation->getClass()) {
                        $parameters[$parameterName] = '$response.body#/'.($uriVariableDefinition->getIdentifiers()[0] ?? 'id');
                    }
                }

                $links[$operationName] = new Link(
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
    private function getFiltersParameters(CollectionOperationInterface|HttpOperation $operation): array
    {
        $parameters = [];
        $resourceFilters = $operation->getFilters();
        $entityClass = $this->getStateOptionsClass($operation, $operation->getClass());

        foreach ($resourceFilters ?? [] as $filterId) {
            if (!$this->filterLocator->has($filterId)) {
                continue;
            }

            $filter = $this->filterLocator->get($filterId);
            foreach ($filter->getDescription($entityClass) as $name => $description) {
                $parameters[] = $this->getFilterParameter($name, $description, $operation->getShortName(), $filterId);
            }
        }

        return $parameters;
    }

    /**
     * @param array<string, mixed> $description
     */
    private function getFilterParameter(string $name, array $description, string $shortName, string $filter): Parameter
    {
        if (isset($description['swagger'])) {
            trigger_deprecation('api-platform/core', '4.0', \sprintf('Using the "swagger" field of the %s::getDescription() (%s) is deprecated.', $filter, $shortName));
        }

        if (!isset($description['openapi']) || $description['openapi'] instanceof Parameter) {
            $schema = $description['schema'] ?? [];

            if (method_exists(PropertyInfoExtractor::class, 'getType')) {
                if (isset($description['type']) && \in_array($description['type'], TypeIdentifier::values(), true) && !isset($schema['type'])) {
                    $type = Type::builtin($description['type']);
                    if ($description['is_collection'] ?? false) {
                        $type = Type::array($type, Type::int());
                    }

                    $schema += $this->getType($type);
                }
            // TODO: remove in 5.x
            } else {
                if (isset($description['type']) && \in_array($description['type'], LegacyType::$builtinTypes, true) && !isset($schema['type'])) {
                    $schema += $this->getType(new LegacyType($description['type'], false, null, $description['is_collection'] ?? false));
                }
            }

            if (!isset($schema['type'])) {
                $schema['type'] = 'string';
            }

            $arrayValueType = method_exists(PropertyInfoExtractor::class, 'getType') ? TypeIdentifier::ARRAY->value : LegacyType::BUILTIN_TYPE_ARRAY;
            $objectValueType = method_exists(PropertyInfoExtractor::class, 'getType') ? TypeIdentifier::OBJECT->value : LegacyType::BUILTIN_TYPE_OBJECT;

            $style = 'array' === ($schema['type'] ?? null) && \in_array(
                $description['type'],
                [$arrayValueType, $objectValueType],
                true
            ) ? 'deepObject' : 'form';

            $parameter = isset($description['openapi']) && $description['openapi'] instanceof Parameter ? $description['openapi'] : new Parameter(in: 'query', name: $name, style: $style, explode: $description['is_collection'] ?? false);

            if ('' === $parameter->getDescription() && ($str = $description['description'] ?? '')) {
                $parameter = $parameter->withDescription($str);
            }

            if (false === $parameter->getRequired() && false !== ($required = $description['required'] ?? false)) {
                $parameter = $parameter->withRequired($required);
            }

            return $parameter->withSchema($schema);
        }

        trigger_deprecation('api-platform/core', '4.0', \sprintf('Not using "%s" on the "openapi" field of the %s::getDescription() (%s) is deprecated.', Parameter::class, $filter, $shortName));

        $schema = $description['schema'] ?? null;

        if (!$schema) {
            if (method_exists(PropertyInfoExtractor::class, 'getType')) {
                if (isset($description['type']) && \in_array($description['type'], TypeIdentifier::values(), true)) {
                    $type = Type::builtin($description['type']);
                    if ($description['is_collection'] ?? false) {
                        $type = Type::array($type, key: Type::int());
                    }
                    $schema = $this->getType($type);
                } else {
                    $schema = ['type' => 'string'];
                }
            // TODO: remove in 5.x
            } else {
                $schema = isset($description['type']) && \in_array($description['type'], LegacyType::$builtinTypes, true)
                    ? $this->getType(new LegacyType($description['type'], false, null, $description['is_collection'] ?? false))
                    : ['type' => 'string'];
            }
        }

        $arrayValueType = method_exists(PropertyInfoExtractor::class, 'getType') ? TypeIdentifier::ARRAY->value : LegacyType::BUILTIN_TYPE_ARRAY;
        $objectValueType = method_exists(PropertyInfoExtractor::class, 'getType') ? TypeIdentifier::OBJECT->value : LegacyType::BUILTIN_TYPE_OBJECT;

        return new Parameter(
            $name,
            'query',
            $description['description'] ?? '',
            $description['required'] ?? false,
            $description['openapi']['deprecated'] ?? false,
            $description['openapi']['allowEmptyValue'] ?? null,
            $schema,
            'array' === $schema['type'] && \in_array(
                $description['type'],
                [$arrayValueType, $objectValueType],
                true
            ) ? 'deepObject' : 'form',
            $description['openapi']['explode'] ?? ('array' === $schema['type']),
            $description['openapi']['allowReserved'] ?? null,
            $description['openapi']['example'] ?? null,
            isset(
                $description['openapi']['examples']
            ) ? new \ArrayObject($description['openapi']['examples']) : null
        );
    }

    private function getPaginationParameters(CollectionOperationInterface|HttpOperation $operation): array
    {
        if (!$this->paginationOptions->isPaginationEnabled()) {
            return [];
        }

        $parameters = [];

        if ($operation->getPaginationEnabled() ?? $this->paginationOptions->isPaginationEnabled()) {
            $parameters[] = new Parameter(
                $this->paginationOptions->getPaginationPageParameterName(),
                'query',
                'The collection page number',
                false,
                false,
                null,
                ['type' => 'integer', 'default' => 1],
            );

            if ($operation->getPaginationClientItemsPerPage() ?? $this->paginationOptions->getClientItemsPerPage()) {
                $schema = [
                    'type' => 'integer',
                    'default' => $operation->getPaginationItemsPerPage() ?? $this->paginationOptions->getItemsPerPage(),
                    'minimum' => 0,
                ];

                if (null !== $maxItemsPerPage = ($operation->getPaginationMaximumItemsPerPage() ?? $this->paginationOptions->getMaximumItemsPerPage())) {
                    $schema['maximum'] = $maxItemsPerPage;
                }

                $parameters[] = new Parameter(
                    $this->paginationOptions->getItemsPerPageParameterName(),
                    'query',
                    'The number of items per page',
                    false,
                    false,
                    null,
                    $schema,
                );
            }
        }

        if ($operation->getPaginationClientEnabled() ?? $this->paginationOptions->isPaginationClientEnabled()) {
            $parameters[] = new Parameter(
                $this->paginationOptions->getPaginationClientEnabledParameterName(),
                'query',
                'Enable or disable pagination',
                false,
                false,
                null,
                ['type' => 'boolean'],
            );
        }

        if ($operation->getPaginationClientPartial() ?? $this->paginationOptions->isClientPartialPaginationEnabled()) {
            $parameters[] = new Parameter($this->paginationOptions->getPartialPaginationParameterName(), 'query', 'Enable or disable partial pagination', false, false, true, ['type' => 'boolean']);
        }

        return $parameters;
    }

    private function getOauthSecurityScheme(): SecurityScheme
    {
        $oauthFlow = new OAuthFlow($this->openApiOptions->getOAuthAuthorizationUrl(), $this->openApiOptions->getOAuthTokenUrl() ?: null, $this->openApiOptions->getOAuthRefreshUrl() ?: null, new \ArrayObject($this->openApiOptions->getOAuthScopes()));
        $description = \sprintf(
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

        return new SecurityScheme($this->openApiOptions->getOAuthType(), $description, null, null, null, null, new OAuthFlows($implicit, $password, $clientCredentials, $authorizationCode), null);
    }

    private function getSecuritySchemes(): array
    {
        $securitySchemes = [];

        if ($this->openApiOptions->getOAuthEnabled()) {
            $securitySchemes['oauth'] = $this->getOauthSecurityScheme();
        }

        foreach ($this->openApiOptions->getApiKeys() as $key => $apiKey) {
            $description = \sprintf('Value for the %s %s parameter.', $apiKey['name'], $apiKey['type']);
            $securitySchemes[$key] = new SecurityScheme('apiKey', $description, $apiKey['name'], $apiKey['type']);
        }

        foreach ($this->openApiOptions->getHttpAuth() as $key => $httpAuth) {
            $description = \sprintf('Value for the http %s parameter.', $httpAuth['scheme']);
            $securitySchemes[$key] = new SecurityScheme('http', $description, null, null, $httpAuth['scheme'], $httpAuth['bearerFormat'] ?? null);
        }

        return $securitySchemes;
    }

    /**
     * @param \ArrayObject<string, mixed> $schemas
     * @param \ArrayObject<string, mixed> $definitions
     */
    private function appendSchemaDefinitions(\ArrayObject $schemas, \ArrayObject $definitions): void
    {
        foreach ($definitions as $key => $value) {
            $schemas[$key] = $value;
        }
    }

    /**
     * @return array{0: int, 1: Parameter}|null
     */
    private function hasParameter(Operation $operation, Parameter $parameter): ?array
    {
        foreach ($operation->getParameters() as $key => $existingParameter) {
            if ($existingParameter->getName() === $parameter->getName() && $existingParameter->getIn() === $parameter->getIn()) {
                return [$key, $existingParameter];
            }
        }

        return null;
    }

    private function mergeParameter(Parameter $actual, Parameter $defined): Parameter
    {
        foreach (
            [
                'name',
                'in',
                'description',
                'required',
                'deprecated',
                'allowEmptyValue',
                'style',
                'explode',
                'allowReserved',
                'example',
            ] as $method
        ) {
            $newValue = $defined->{"get$method"}();
            if (null !== $newValue && $actual->{"get$method"}() !== $newValue) {
                $actual = $actual->{"with$method"}($newValue);
            }
        }

        foreach (['examples', 'content', 'schema'] as $method) {
            $newValue = $defined->{"get$method"}();
            if ($newValue && \count($newValue) > 0 && $actual->{"get$method"}() !== $newValue) {
                $actual = $actual->{"with$method"}($newValue);
            }
        }

        return $actual;
    }

    /**
     * @param ErrorResource[]              $errors
     * @param \ArrayObject<string, Schema> $schemas
     */
    private function addOperationErrors(
        Operation $operation,
        array $errors,
        ResourceMetadataCollection $resourceMetadataCollection,
        Schema $schema,
        \ArrayObject $schemas,
        HttpOperation $originalOperation,
    ): Operation {
        foreach ($errors as $errorResource) {
            $responseMimeTypes = $this->flattenMimeTypes($errorResource->getOutputFormats() ?: $this->errorFormats);
            foreach ($errorResource->getOperations() as $errorOperation) {
                if (false === $errorOperation->getOpenApi()) {
                    continue;
                }

                $responseMimeTypes += $this->flattenMimeTypes($errorOperation->getOutputFormats() ?: $this->errorFormats);
            }

            foreach ($responseMimeTypes as $mime => $format) {
                if (!isset($this->errorFormats[$format])) {
                    unset($responseMimeTypes[$mime]);
                }
            }

            $operationErrorSchemas = [];
            foreach ($responseMimeTypes as $operationFormat) {
                $operationErrorSchema = null;
                // Having JSONSchema for non-json schema makes no sense
                if (str_starts_with($operationFormat, 'json')) {
                    $operationErrorSchema = $this->jsonSchemaFactory->buildSchema($errorResource->getClass(), $operationFormat, Schema::TYPE_OUTPUT, null, $schema);
                    $this->appendSchemaDefinitions($schemas, $operationErrorSchema->getDefinitions());
                }
                $operationErrorSchemas[$operationFormat] = $operationErrorSchema;
            }

            if (!$status = $errorResource->getStatus()) {
                throw new RuntimeException(\sprintf('The error class "%s" has no status defined, please either implement ProblemExceptionInterface, or make it an ErrorResource with a status', $errorResource->getClass()));
            }

            $operation = $this->buildOpenApiResponse($operation->getResponses() ?: [], $status, $errorResource->getDescription() ?? '', $operation, $originalOperation, $responseMimeTypes, $operationErrorSchemas, $resourceMetadataCollection);
        }

        return $operation;
    }

    /**
     * @param string|class-string $error
     */
    private function getErrorResource(string $error, ?int $status = null, ?string $description = null): ErrorResource
    {
        if ($this->localErrorResourceCache[$error] ?? null) {
            return $this->localErrorResourceCache[$error];
        }

        if (is_a($error, ProblemExceptionInterface::class, true)) {
            try {
                /** @var ProblemExceptionInterface $exception */
                $exception = new $error();
                $status = $exception->getStatus();
                $description = $exception->getTitle();
            } catch (\TypeError) {
            }
        } elseif (class_exists($error)) {
            throw new RuntimeException(\sprintf('The error class "%s" does not implement "%s". Did you forget a use statement?', $error, ProblemExceptionInterface::class));
        }

        $defaultErrorResourceClass = $this->openApiOptions->getErrorResourceClass() ?? ApiResourceError::class;

        try {
            $errorResource = $this->resourceMetadataFactory->create($error)[0] ?? new ErrorResource(status: $status, description: $description, class: $defaultErrorResourceClass);
            if (!($errorResource instanceof ErrorResource)) {
                throw new RuntimeException(\sprintf('The error class %s is not an ErrorResource', $error));
            }

            // Here we want the exception status and expression to override the resource one when available
            if ($status) {
                $errorResource = $errorResource->withStatus($status);
            }

            if ($description) {
                $errorResource = $errorResource->withDescription($description);
            }
        } catch (ResourceClassNotFoundException|OperationNotFoundException) {
            $errorResource = new ErrorResource(status: $status, description: $description, class: $defaultErrorResourceClass);
        }

        if (!$errorResource->getClass()) {
            $errorResource = $errorResource->withClass($error);
        }

        return $this->localErrorResourceCache[$error] = $errorResource;
    }
}
