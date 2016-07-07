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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\ApiDocumentationBuilderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\PropertyInfo\Type;

/**
 * Creates a machine readable Swagger API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiDocumentationBuilder implements ApiDocumentationBuilderInterface
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $contextBuilder;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $urlGenerator;
    private $title;
    private $description;
    private $iriConverter;
    private $version;
    private $host;
    private $schema;
    const SWAGGER_VERSION = '2.0';

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, UrlGeneratorInterface $urlGenerator, IriConverterInterface $iriConverter, string $title, string $description, string $version = null, string $host, string $schema)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->contextBuilder = $contextBuilder;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->urlGenerator = $urlGenerator;
        $this->title = $title;
        $this->description = $description;
        $this->iriConverter = $iriConverter;
        $this->version = $version;
        $this->host = $host;
        $this->schema[] = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDocumentation()
    {
        $classes = [];
        $itemOperations = [];
        $itemOperations['operation'] = [];

        $itemOperationsDocs = [];
        $properties = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = ($iri = $resourceMetadata->getIri()) ? $iri : '#'.$shortName;

            $class = [
                'name' => $shortName,
                'externalDocs' => [
                    'url' => $prefixedShortName,
                ],
            ];

            if ($description = $resourceMetadata->getDescription()) {
                $class = [
                    'name' => $shortName,
                    'description' => $description,
                    'externalDocs' => [
                        'url' => $prefixedShortName,
                    ],
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

                if ($propertyMetadata->isIdentifier() && !$propertyMetadata->isWritable()) {
                    continue;
                }
                $range = $this->getRange($propertyMetadata);

                $property[$propertyName] = [
                    'type' => $range,
                ];

                if (is_array($range)) {
                    $property[$propertyName] = $range;
                }

                $required = [];

                if ($propertyMetadata->isRequired()) {
                    $required = array_merge($required, [$propertyName]);
                }

                if (!empty($required)) {
                    $properties[$shortName]['required'] = $required;
                }

                $properties[$shortName]['type'] = 'object';
                $properties[$shortName]['properties'] = $property;
            }

            if ($operations = $resourceMetadata->getItemOperations()) {
                foreach ($operations as $operationName => $itemOperation) {
                    $swaggerOperation = $this->getSwaggerOperation($resourceClass, $resourceMetadata, $operationName, $itemOperation, $prefixedShortName, false);
                    $itemOperations['operation'] = array_merge($itemOperations['operation'], $swaggerOperation);
                }
            }

            try {
                $resourceClassIri = $this->iriConverter->getIriFromResourceClass($resourceClass);
            } catch (InvalidArgumentException $e) {
                $resourceClassIri = '/nopaths';
            }
            $resourceClassIri .= '/{id}';

            $itemOperationsDocs[$resourceClassIri] = $itemOperations['operation'];
            $classes[] = $class;
        }

        $doc['swagger'] = self::SWAGGER_VERSION;
        if ('' !== $this->title) {
            $doc['info']['title'] = $this->title;
        }

        if ('' !== $this->description) {
            $doc['info']['description'] = $this->description;
        }
        $doc['info']['version'] = $this->version ?? '0.0.0';
        $doc['host'] = $this->host;
        $doc['basePath'] = $this->urlGenerator->generate('api_jsonld_entrypoint');
        $doc['definitions'] = $properties;
        $doc['externalDocs'] = ['description' => 'Find more about API Platform', 'url' => 'https://api-platform.com'];
        $doc['tags'] = $classes;
        $doc['schemes'] = $this->schema; // more schema ?
        $doc['paths'] = $itemOperationsDocs;

        return $doc;
    }

    /**
     * Gets and populates if applicable a Swagger operation.
     */
    private function getSwaggerOperation(string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $operation, string $prefixedShortName, bool $collection) : array
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
        }
        $methodSwagger = strtolower($method);
        $swaggerOperation = $operation['swagger_context'] ?? [];
        $shortName = $resourceMetadata->getShortName();
        $swaggerOperation[$methodSwagger] = [];
        $swaggerOperation[$methodSwagger]['tags'] = [$shortName];
        $swaggerOperation[$methodSwagger]['produces'] = ['application/ld+json'];
        $swaggerOperation[$methodSwagger]['consumes'] = $swaggerOperation[$methodSwagger]['produces'];
        switch ($method) {
            case 'GET':
                if ($collection) {
                    if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                        $swaggerOperation[$methodSwagger]['summary'] = sprintf('Retrieves the collection of %s resources.', $shortName);
                    }
                } else {
                    if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                        $swaggerOperation[$methodSwagger]['summary'] = sprintf('Retrieves %s resource.', $shortName);
                    }
                    $swaggerOperation[$methodSwagger]['parameters'][] = [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'type' => 'integer',
                    ];
                }
                $swaggerOperation[$methodSwagger]['responses'] = [
                    '200' => ['description' => 'Valid ID'],
                ];
                break;

            case 'POST':
                if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                    $swaggerOperation[$methodSwagger]['summary'] = sprintf('Creates a %s resource.', $shortName);
                }
                if ($this->resourceClassResolver->isResourceClass($shortName)) {
                    $swaggerOperation[$methodSwagger]['parameters'] = [
                        'in' => 'body',
                        'name' => 'body',
                        'description' => sprintf('%s resource to be added', $shortName),
                        'schema' => [
                            '$ref' => sprintf('#/definitions/%s', $shortName),
                        ],
                    ];
                }

                $swaggerOperation[$methodSwagger]['responses'] = [
                        '201' => ['description' => 'Valid ID'],
                    ];

            break;

            case 'PUT':
                if (!isset($swaggerOperation[$methodSwagger]['title'])) {
                    $swaggerOperation[$methodSwagger]['summary'] = sprintf('Replaces the %s resource.', $shortName);
                }
                $swaggerOperation[$methodSwagger]['parameters'] = [[
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'type' => 'integer',
                ]];
                if ($this->resourceClassResolver->isResourceClass($shortName)) {
                    $swaggerOperation[$methodSwagger]['parameters'] = [[
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
                    ], ];
                }

                $swaggerOperation[$methodSwagger]['responses'] = [
                    '200' => ['description' => 'Valid ID'],
                ];
            break;

            case 'DELETE':
                $swaggerOperation[$methodSwagger]['responses'] = [
                    '204' => ['description' => 'Deleted'],
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
     * @return string|null
     */
    private function getRange(PropertyMetadata $propertyMetadata)
    {
        $type = $propertyMetadata->getType();
        if (!$type) {
            return;
        }

        if ($type->isCollection() && $collectionType = $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return 'string';

            case Type::BUILTIN_TYPE_INT:
                return 'integer';

            case Type::BUILTIN_TYPE_FLOAT:
                return 'number';

            case Type::BUILTIN_TYPE_BOOL:
                return 'boolean';

            case Type::BUILTIN_TYPE_OBJECT:
                $className = $type->getClassName();

                if (null !== $className) {
                    $reflection = new \ReflectionClass($className);
                    if ($reflection->implementsInterface(\DateTimeInterface::class)) {
                        return 'string';
                    }

                    $className = $type->getClassName();
                    if ($this->resourceClassResolver->isResourceClass($className)) {
                        return ['$ref' => sprintf('#/definitions/%s', $this->resourceMetadataFactory->create($className)->getShortName())];
                    }
                }
            break;
            default:
                return 'null';
            break;
        }
    }
}
