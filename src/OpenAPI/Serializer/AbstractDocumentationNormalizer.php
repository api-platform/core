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

namespace ApiPlatform\Core\OpenAPI\Serializer;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Common features regarding Documentation normalization.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 *
 * @internal
 */
abstract class AbstractDocumentationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    const FORMAT = 'json';
    const ATTRIBUTE_NAME = 'openapi_context';
    const BASE_URL = 'base_url';

    protected $resourceMetadataFactory;
    protected $propertyNameCollectionFactory;
    protected $propertyMetadataFactory;
    protected $resourceClassResolver;
    protected $operationMethodResolver;
    protected $operationPathResolver;
    protected $nameConverter;
    protected $oauthEnabled;
    protected $oauthType;
    protected $oauthFlow;
    protected $oauthTokenUrl;
    protected $oauthAuthorizationUrl;
    protected $oauthScopes;
    protected $apiKeys;
    protected $subresourceOperationFactory;
    protected $paginationEnabled;
    protected $paginationPageParameterName;
    protected $clientItemsPerPage;
    protected $itemsPerPageParameterName;
    protected $paginationClientEnabled;
    protected $paginationClientEnabledParameterName;
    protected $formatsProvider;
    protected $defaultContext = [self::BASE_URL => '/'];

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     */
    protected function getPath(string $resourceShortName, string $operationName, array $operation, string $operationType): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets the Swagger's type corresponding to the given PHP's type.
     *
     * @param string $className
     * @param bool   $readableLink
     */
    protected function getType(string $type, bool $isCollection, string $className = null, bool $readableLink = null, \ArrayObject $definitions, array $serializerContext = null): array
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
     * @return array|null
     */
    protected function getSerializerContext(string $operationType, bool $denormalization, ResourceMetadata $resourceMetadata, string $operationName)
    {
        $contextKey = $denormalization ? 'denormalization_context' : 'normalization_context';

        if (OperationType::COLLECTION === $operationType) {
            return $resourceMetadata->getCollectionOperationAttribute($operationName, $contextKey, null, true);
        }

        return $resourceMetadata->getItemOperationAttribute($operationName, $contextKey, null, true);
    }

    protected function getDefinition(\ArrayObject $definitions, ResourceMetadata $resourceMetadata, string $resourceClass, array $serializerContext = null): string
    {
        if (isset($serializerContext[DocumentationNormalizer::SWAGGER_DEFINITION_NAME])) {
            $definitionKey = sprintf('%s-%s', $resourceMetadata->getShortName(), $serializerContext[DocumentationNormalizer::SWAGGER_DEFINITION_NAME]);
        } else {
            $definitionKey = $this->getDefinitionKey($resourceMetadata->getShortName(), (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []));
        }

        if (!isset($definitions[$definitionKey])) {
            $definitions[$definitionKey] = [];  // Initialize first to prevent infinite loop
            $definitions[$definitionKey] = $this->getDefinitionSchema($resourceClass, $resourceMetadata, $definitions, $serializerContext);
        }

        return $definitionKey;
    }

    protected function getDefinitionKey(string $resourceShortName, array $groups): string
    {
        return $groups ? sprintf('%s-%s', $resourceShortName, implode('_', $groups)) : $resourceShortName;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     */
    protected function getDefinitionSchema(string $resourceClass, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
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
            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT, $serializerContext ?? []) : $propertyName;
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
     */
    protected function getPropertySchema(PropertyMetadata $propertyMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $propertySchema = new \ArrayObject($propertyMetadata->getAttributes()[static::ATTRIBUTE_NAME] ?? []);

        if (false === $propertyMetadata->isWritable() && !$propertyMetadata->isInitializable()) {
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
     * Gets a path Operation Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operation-object
     *
     * @param string[] $mimeTypes
     */
    protected function getPathOperation(string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes, \ArrayObject $definitions): \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation[static::ATTRIBUTE_NAME] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($operationType);
        if ($resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) {
            $pathOperation['deprecated'] = true;
        }
        if (null !== $this->formatsProvider) {
            $responseFormats = $this->formatsProvider->getFormatsFromOperation($resourceClass, $operationName, $operationType);
            $responseMimeTypes = $this->extractMimeTypes($responseFormats);
        }
        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'POST':
                return $this->updatePostOperation($pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
            // no break
            case 'PUT':
                return $this->updatePutOperation($pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'DELETE':
                return $this->updateDeleteOperation($pathOperation, $resourceShortName);
        }

        return $pathOperation;
    }

    abstract protected function updateGetOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions);

    abstract protected function updatePostOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions);

    abstract protected function updatePutOperation(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions);

    abstract protected function updateDeleteOperation(\ArrayObject $pathOperation, string $resourceShortName);

    /**
     * Updates the list of entries in the paths collection.
     */
    protected function addPaths(\ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType)
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
     * Returns pagination parameters for the "get" collection operation.
     */
    protected function getPaginationParameters(): array
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
     * Returns enable pagination parameter for the "get" collection operation.
     */
    protected function getPaginationClientEnabledParameters(): array
    {
        return [
            'name' => $this->paginationClientEnabledParameterName,
            'in' => 'query',
            'required' => false,
            'type' => 'boolean',
            'description' => 'Enable or disable pagination',
        ];
    }

    /**
     * Returns items per page parameters for the "get" collection operation.
     */
    protected function getItemsPerPageParameters(): array
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
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    protected function extractMimeTypes(array $responseFormats): array
    {
        $responseMimeTypes = [];
        foreach ($responseFormats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $responseMimeTypes[] = $mimeType;
            }
        }

        return $responseMimeTypes;
    }
}
