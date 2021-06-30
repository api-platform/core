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

namespace ApiPlatform\Tests\Metadata\Extractor;

use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;
use ApiPlatform\Metadata\Extractor\YamlResourceExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\NormalizerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\OperationDefaultsTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\State\OptionsInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Comment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTranslation;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\ResourceAdapterInterface;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\XmlResourceAdapter;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\YamlResourceAdapter;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Ensures XML and YAML mappings are fully compatible with ApiResource.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ResourceMetadataCompatibilityTest extends TestCase
{
    use OperationDefaultsTrait;
    private const RESOURCE_CLASS = Comment::class;
    private const SHORT_NAME = 'Comment';
    private const DEFAULTS = [
        'route_prefix' => '/v1',
    ];
    private const FIXTURES = [
        null,
        [
            'uriTemplate' => '/users/{userId}/comments',
            'shortName' => self::SHORT_NAME,
            'description' => 'A list of Comments from User',
            'routePrefix' => '/api',
            'stateless' => true,
            'sunset' => '2021-01-01',
            'acceptPatch' => 'application/merge-patch+json',
            'status' => 200,
            'host' => 'example.com',
            'condition' => 'request.headers.get(\'User-Agent\') matches \{/firefox/i\'',
            'controller' => 'App\Controller\CommentController',
            'urlGenerationStrategy' => 1,
            'deprecationReason' => 'This resource is deprecated',
            'elasticsearch' => true,
            'messenger' => true,
            'input' => 'App\Dto\CommentInput',
            'output' => 'App\Dto\CommentOutut',
            'fetchPartial' => true,
            'forceEager' => true,
            'paginationClientEnabled' => true,
            'paginationClientItemsPerPage' => true,
            'paginationClientPartial' => true,
            'paginationEnabled' => true,
            'paginationFetchJoinCollection' => true,
            'paginationUseOutputWalkers' => true,
            'paginationItemsPerPage' => 42,
            'paginationMaximumItemsPerPage' => 200,
            'paginationPartial' => true,
            'paginationType' => 'page',
            'security' => 'is_granted(\'ROLE_USER\')',
            'securityMessage' => 'Sorry, you can\'t access this resource.',
            'securityPostDenormalize' => 'is_granted(\'ROLE_ADMIN\')',
            'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this resource.',
            'securityPostValidation' => 'is_granted(\'ROLE_OWNER\')',
            'securityPostValidationMessage' => 'Sorry, you must the owner of this resource to access it.',
            'queryParameterValidationEnabled' => true,
            'types' => ['someirischema', 'anotheririschema'],
            'formats' => [
                'json' => null,
                'jsonld' => null,
                'xls' => 'application/vnd.ms-excel',
            ],
            'inputFormats' => [
                'json' => 'application/merge-patch+json',
            ],
            'outputFormats' => [
                'json' => 'application/merge-patch+json',
            ],
            'uriVariables' => [
                'userId' => [
                    'fromClass' => Comment::class,
                    'fromProperty' => 'author',
                    'compositeIdentifier' => true,
                ],
            ],
            'defaults' => [
                'prout' => 'pouet',
            ],
            'requirements' => [
                'id' => '\d+',
            ],
            'options' => [
                'foo' => 'bar',
            ],
            'schemes' => ['http', 'https'],
            'cacheHeaders' => [
                'max_age' => 60,
                'shared_max_age' => 120,
                'vary' => ['Authorization', 'Accept-Language'],
            ],
            'normalizationContext' => [
                'groups' => 'comment:read',
            ],
            'denormalizationContext' => [
                'groups' => ['comment:write', 'comment:custom'],
            ],
            'collectDenormalizationErrors' => true,
            'hydraContext' => [
                'foo' => ['bar' => 'baz'],
            ],
            // TODO Remove in 4.0
            'openapiContext' => [
                'bar' => 'baz',
            ],
            'openapi' => [
                'extensionProperties' => [
                    'bar' => 'baz',
                ],
            ],
            'validationContext' => [
                'foo' => 'bar',
            ],
            'filters' => ['comment.custom_filter'],
            'order' => ['foo', 'bar'],
            'paginationViaCursor' => [
                'id' => 'DESC',
            ],
            'exceptionToStatus' => [
                'Symfony\Component\Serializer\Exception\ExceptionInterface' => 400,
            ],
            'translation' => [
                'class' => DummyTranslation::class,
                'allTranslationsEnabled' => true,
                'allTranslationsClientEnabled' => false,
                'allTranslationsClientParameterName' => 'allT',
            ],
            'extraProperties' => [
                'custom_property' => 'Lorem ipsum dolor sit amet',
                'another_custom_property' => [
                    'Lorem ipsum' => 'Dolor sit amet',
                ],
            ],
            'mercure' => true,
            'stateOptions' => [
                'elasticsearchOptions' => [
                    'index' => 'foo_index',
                    'type' => 'foo_type',
                ],
            ],
            'graphQlOperations' => [
                [
                    'args' => [
                        'foo' => [
                            'type' => 'custom',
                            'bar' => 'baz',
                        ],
                    ],
                    'extraArgs' => [
                        'bar' => [
                            'type' => 'custom',
                            'baz' => 'qux',
                        ],
                    ],
                    'shortName' => self::SHORT_NAME,
                    'description' => 'Creates a Comment.',
                    'class' => Mutation::class,
                    'name' => 'create',
                    'urlGenerationStrategy' => 0,
                    'deprecationReason' => 'I don\'t know',
                    'normalizationContext' => [
                        'groups' => 'comment:read_collection',
                    ],
                    'denormalizationContext' => [
                        'groups' => ['comment:write'],
                    ],
                    'validationContext' => [
                        'foo' => 'bar',
                    ],
                    'filters' => ['comment.another_custom_filter'],
                    'elasticsearch' => false,
                    'mercure' => [
                        'private' => true,
                    ],
                    'messenger' => 'input',
                    'input' => 'App\Dto\CreateCommentInput',
                    'output' => 'App\Dto\CommentCollectionOutut',
                    'order' => ['userId'],
                    'fetchPartial' => false,
                    'forceEager' => false,
                    'paginationClientEnabled' => false,
                    'paginationClientItemsPerPage' => false,
                    'paginationClientPartial' => false,
                    'paginationEnabled' => false,
                    'paginationFetchJoinCollection' => false,
                    'paginationUseOutputWalkers' => false,
                    'paginationItemsPerPage' => 54,
                    'paginationMaximumItemsPerPage' => 200,
                    'paginationPartial' => false,
                    'paginationType' => 'page',
                    'security' => 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
                    'securityMessage' => 'Sorry, you can\'t access this collection.',
                    'securityPostDenormalize' => 'is_granted(\'ROLE_CUSTOM_ADMIN\')',
                    'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this collection.',
                    'read' => true,
                    'deserialize' => false,
                    'validate' => false,
                    'write' => false,
                    'serialize' => true,
                    'priority' => 200,
                    'extraProperties' => [
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                        'foo' => 'bar',
                        'route_prefix' => '/v1', // from defaults
                    ],
                    'stateOptions' => [
                        'elasticsearchOptions' => [
                            'index' => 'foo_index',
                            'type' => 'foo_type',
                        ],
                    ],
                ],
                [
                    'class' => Query::class,
                    'extraProperties' => [
                        'route_prefix' => '/v1',
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                    ],
                    'stateOptions' => [
                        'elasticsearchOptions' => [
                            'index' => 'foo_index',
                            'type' => 'foo_type',
                        ],
                    ],
                ],
                [
                    'class' => QueryCollection::class,
                    'extraProperties' => [
                        'route_prefix' => '/v1',
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                    ],
                    'stateOptions' => [
                        'elasticsearchOptions' => [
                            'index' => 'foo_index',
                            'type' => 'foo_type',
                        ],
                    ],
                ],
                [
                    'class' => Subscription::class,
                    'extraProperties' => [
                        'route_prefix' => '/v1',
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                    ],
                    'stateOptions' => [
                        'elasticsearchOptions' => [
                            'index' => 'foo_index',
                            'type' => 'foo_type',
                        ],
                    ],
                ],
            ],
            'operations' => [
                [
                    'name' => 'custom_operation_name',
                    'method' => 'GET',
                    'uriTemplate' => '/users/{userId}/comments{._format}',
                    'shortName' => self::SHORT_NAME,
                    'description' => 'A list of Comments',
                    'types' => ['Comment'],
                    'formats' => [
                        'json' => null,
                        'jsonld' => null,
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ],
                    'inputFormats' => [
                        'jsonld' => 'application/merge-patch+json+ld',
                    ],
                    'outputFormats' => [
                        'jsonld' => 'application/merge-patch+json+ld',
                    ],
                    'uriVariables' => [
                        'userId' => [
                            'fromClass' => Comment::class,
                            'fromProperty' => 'author',
                            'compositeIdentifier' => true,
                        ],
                    ],
                    'routePrefix' => '/foo/api',
                    'defaults' => [
                        '_bar' => '_foo',
                    ],
                    'requirements' => [
                        'userId' => '\d+',
                    ],
                    'options' => [
                        'bar' => 'baz',
                    ],
                    'stateless' => false,
                    'sunset' => '2021-12-01',
                    'acceptPatch' => 'text/example;charset=utf-8',
                    'status' => 204,
                    'host' => 'api-platform.com',
                    'schemes' => ['https'],
                    'condition' => 'request.headers.has(\'Accept\')',
                    'controller' => 'App\Controller\CustomController',
                    'class' => GetCollection::class,
                    'urlGenerationStrategy' => 0,
                    'deprecationReason' => 'I don\'t know',
                    'cacheHeaders' => [
                        'max_age' => 60,
                        'shared_max_age' => 120,
                        'vary' => ['Authorization', 'Accept-Language', 'Accept'],
                    ],
                    'normalizationContext' => [
                        'groups' => 'comment:read_collection',
                    ],
                    'denormalizationContext' => [
                        'groups' => ['comment:write'],
                    ],
                    'hydraContext' => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    // TODO Remove in 4.0
                    'openapiContext' => [
                        'bar' => 'baz',
                    ],
                    'openapi' => [
                        'extensionProperties' => [
                            'bar' => 'baz',
                        ],
                    ],
                    'validationContext' => [
                        'foo' => 'bar',
                    ],
                    'filters' => ['comment.another_custom_filter'],
                    'elasticsearch' => false,
                    'mercure' => [
                        'private' => true,
                    ],
                    'messenger' => 'input',
                    'input' => 'App\Dto\CreateCommentInput',
                    'output' => 'App\Dto\CommentCollectionOutut',
                    'order' => ['userId'],
                    'fetchPartial' => false,
                    'forceEager' => false,
                    'paginationClientEnabled' => false,
                    'paginationClientItemsPerPage' => false,
                    'paginationClientPartial' => false,
                    'paginationViaCursor' => [
                        'userId' => 'DESC',
                    ],
                    'paginationEnabled' => false,
                    'paginationFetchJoinCollection' => false,
                    'paginationUseOutputWalkers' => false,
                    'paginationItemsPerPage' => 54,
                    'paginationMaximumItemsPerPage' => 200,
                    'paginationPartial' => false,
                    'paginationType' => 'page',
                    'security' => 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
                    'securityMessage' => 'Sorry, you can\'t access this collection.',
                    'securityPostDenormalize' => 'is_granted(\'ROLE_CUSTOM_ADMIN\')',
                    'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this collection.',
                    'exceptionToStatus' => [
                        'Symfony\Component\Serializer\Exception\ExceptionInterface' => 404,
                    ],
                    'queryParameterValidationEnabled' => false,
                    'read' => true,
                    'deserialize' => false,
                    'validate' => false,
                    'write' => false,
                    'serialize' => true,
                    'priority' => 200,
                    'extraProperties' => [
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                        'foo' => 'bar',
                    ],
                ],
                [
                    'uriTemplate' => '/users/{userId}/comments/{commentId}{._format}',
                    'class' => Get::class,
                    'uriVariables' => [
                        'userId' => [
                            'fromClass' => Comment::class,
                            'fromProperty' => 'author',
                            'compositeIdentifier' => true,
                        ],
                        'commentId' => [Comment::class, 'id'],
                    ],
                ],
            ],
        ],
    ];
    private const BASE = [
        'shortName',
        'description',
        'urlGenerationStrategy',
        'deprecationReason',
        'elasticsearch',
        'messenger',
        'mercure',
        'input',
        'output',
        'fetchPartial',
        'forceEager',
        'paginationClientEnabled',
        'paginationClientItemsPerPage',
        'paginationClientPartial',
        'paginationEnabled',
        'paginationFetchJoinCollection',
        'paginationUseOutputWalkers',
        'paginationItemsPerPage',
        'paginationMaximumItemsPerPage',
        'paginationPartial',
        'paginationType',
        'processor',
        'provider',
        'security',
        'securityMessage',
        'securityPostDenormalize',
        'securityPostDenormalizeMessage',
        'securityPostValidation',
        'securityPostValidationMessage',
        'normalizationContext',
        'denormalizationContext',
        'collectDenormalizationErrors',
        'validationContext',
        'translation',
        'filters',
        'order',
        'extraProperties',
    ];
    private const EXTENDED_BASE = [
        'uriTemplate',
        'routePrefix',
        'stateless',
        'sunset',
        'acceptPatch',
        'status',
        'host',
        'condition',
        'controller',
        'queryParameterValidationEnabled',
        'exceptionToStatus',
        'types',
        'formats',
        'inputFormats',
        'outputFormats',
        'uriVariables',
        'defaults',
        'requirements',
        'options',
        'schemes',
        'cacheHeaders',
        'hydraContext',
        // TODO Remove in 4.0
        'openapiContext',
        'openapi',
        'paginationViaCursor',
        'stateOptions',
    ];

    /**
     * @dataProvider getExtractors
     */
    public function testValidMetadata(string $extractorClass, ResourceAdapterInterface $adapter): void
    {
        $reflClass = new \ReflectionClass(ApiResource::class);
        $parameters = $reflClass->getConstructor()->getParameters();
        $this->defaults = self::DEFAULTS;
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();

        try {
            $extractor = new $extractorClass($adapter(self::RESOURCE_CLASS, $parameters, self::FIXTURES));
            $factory = new ExtractorResourceMetadataCollectionFactory($extractor, null, self::DEFAULTS, null, true);
            $collection = $factory->create(self::RESOURCE_CLASS);
        } catch (\Exception $exception) {
            throw new AssertionFailedError('Failed asserting that the schema is valid according to '.ApiResource::class, 0, $exception);
        }

        $resourceFactory = new class($this->buildApiResources()) implements ResourceMetadataCollectionFactoryInterface {
            public function __construct(private readonly array $resources)
            {
            }

            public function create(string $resourceClass): ResourceMetadataCollection
            {
                return new ResourceMetadataCollection($resourceClass, $this->resources);
            }
        };

        $this->assertEquals(
            (new NormalizerResourceMetadataCollectionFactory($resourceFactory))->create(self::RESOURCE_CLASS),
            $collection
        );
    }

    public function getExtractors(): array
    {
        return [
            [XmlResourceExtractor::class, new XmlResourceAdapter()],
            [YamlResourceExtractor::class, new YamlResourceAdapter()],
        ];
    }

    /**
     * @return ApiResource[]
     */
    private function buildApiResources(): array
    {
        $resources = [];

        foreach (self::FIXTURES as $fixtures) {
            $resource = (new ApiResource())->withClass(self::RESOURCE_CLASS)->withShortName(self::SHORT_NAME);

            if (null === $fixtures) {
                // Build default operations
                $operations = [];
                foreach ([new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()] as $operation) {
                    [$name, $operation] = $this->getOperationWithDefaults($resource, $operation);
                    $operations[$name] = $operation;
                }

                $resource = $resource->withOperations(new Operations($operations));

                // Build default GraphQL operations
                $graphQlOperations = [];
                foreach ([new QueryCollection(), new Query(), (new Mutation())->withName('update'), (new DeleteMutation())->withName('delete'), (new Mutation())->withName('create')] as $graphQlOperation) {
                    $description = $graphQlOperation instanceof Mutation ? ucfirst("{$graphQlOperation->getName()}s a {$resource->getShortName()}.") : null;
                    [$name, $operation] = $this->getOperationWithDefaults($resource, $graphQlOperation);
                    $graphQlOperations[$name] = $operation->withDescription($description);
                }

                $resources[] = $resource->withGraphQlOperations($graphQlOperations);

                continue;
            }

            foreach ($fixtures as $parameter => $value) {
                if (method_exists($this, 'with'.ucfirst($parameter))) {
                    $value = $this->{'with'.ucfirst($parameter)}($value, $fixtures);
                }

                if (method_exists($resource, 'with'.ucfirst($parameter))) {
                    $resource = $resource->{'with'.ucfirst($parameter)}($value, $fixtures);
                    continue;
                }

                throw new \RuntimeException(sprintf('Unknown ApiResource parameter "%s".', $parameter));
            }

            $resources[] = $resource;
        }

        return $resources;
    }

    private function withOpenapi(array|bool $values): bool|OpenApiOperation
    {
        if (\is_bool($values)) {
            return $values;
        }

        $allowedProperties = array_map(fn (\ReflectionProperty $reflProperty): string => $reflProperty->getName(), (new \ReflectionClass(OpenApiOperation::class))->getProperties());
        foreach ($values as $key => $value) {
            $values[$key] = match ($key) {
                'externalDocs' => new ExternalDocumentation(description: $value['description'] ?? '', url: $value['url'] ?? ''),
                'requestBody' => new RequestBody(description: $value['description'] ?? '', content: isset($value['content']) ? new \ArrayObject($value['content']) : null, required: $value['required'] ?? false),
                'callbacks' => new \ArrayObject($value),
                default => $value,
            };

            if (\in_array($key, $allowedProperties, true)) {
                continue;
            }

            $values['extensionProperties'][$key] = $value;
            unset($values[$key]);
        }

        return new OpenApiOperation(...$values);
    }

    private function withUriVariables(array $values): array
    {
        $uriVariables = [];
        foreach ($values as $parameterName => $value) {
            if (\is_string($value)) {
                $uriVariables[$value] = $value;
                continue;
            }

            if (isset($value['fromClass']) || isset($value[0])) {
                $uriVariables[$parameterName]['from_class'] = $value['fromClass'] ?? $value[0];
            }
            if (isset($value['fromProperty']) || isset($value[1])) {
                $uriVariables[$parameterName]['from_property'] = $value['fromProperty'] ?? $value[1];
            }
            if (isset($value['toClass'])) {
                $uriVariables[$parameterName]['to_class'] = $value['toClass'];
            }
            if (isset($value['toProperty'])) {
                $uriVariables[$parameterName]['to_property'] = $value['toProperty'];
            }
            if (isset($value['identifiers'])) {
                $uriVariables[$parameterName]['identifiers'] = $value['identifiers'];
            }
            if (isset($value['compositeIdentifier'])) {
                $uriVariables[$parameterName]['composite_identifier'] = $value['compositeIdentifier'];
            }
        }

        return $uriVariables;
    }

    private function withOperations(array $values, ?array $fixtures): Operations
    {
        $operations = [];
        foreach ($values as $value) {
            $class = $value['class'] ?? HttpOperation::class;
            unset($value['class']);
            $operation = (new $class())->withClass(self::RESOURCE_CLASS);

            foreach (array_merge(self::BASE, self::EXTENDED_BASE) as $parameter) {
                if ((!\array_key_exists($parameter, $value) || null === $value[$parameter]) && isset($fixtures[$parameter])) {
                    $value[$parameter] = $fixtures[$parameter];
                }
            }

            foreach ($value as $parameter => $parameterValue) {
                if (method_exists($this, 'with'.ucfirst($parameter))) {
                    $parameterValue = $this->{'with'.ucfirst($parameter)}($parameterValue);
                }

                if (method_exists($operation, 'with'.ucfirst($parameter))) {
                    $operation = $operation->{'with'.ucfirst($parameter)}($parameterValue);
                    continue;
                }

                throw new \RuntimeException(sprintf('Unknown Operation parameter "%s".', $parameter));
            }

            $operationName = $operation->getName() ?? $this->getDefaultOperationName($operation, self::RESOURCE_CLASS);
            $operations[$operationName] = $operation;
        }

        return new Operations($operations);
    }

    private function withGraphQlOperations(array $values, ?array $fixtures): array
    {
        $operations = [];
        foreach ($values as $value) {
            $class = $value['class'];
            unset($value['class']);
            $operation = (new $class())->withClass(self::RESOURCE_CLASS);

            foreach (self::BASE as $parameter) {
                if ((!\array_key_exists($parameter, $value) || null === $value[$parameter]) && isset($fixtures[$parameter])) {
                    $value[$parameter] = $fixtures[$parameter];
                }
            }

            foreach ($value as $parameter => $parameterValue) {
                if (method_exists($this, 'with'.ucfirst($parameter))) {
                    $parameterValue = $this->{'with'.ucfirst($parameter)}($parameterValue);
                }

                if (method_exists($operation, 'with'.ucfirst($parameter))) {
                    $operation = $operation->{'with'.ucfirst($parameter)}($parameterValue);
                    continue;
                }

                throw new \RuntimeException(sprintf('Unknown GraphQlOperation parameter "%s".', $parameter));
            }

            $operationName = $operation->getName();
            $operations[$operationName] = $operation;
        }

        return $operations;
    }

    private function withStateOptions(array $values): ?OptionsInterface
    {
        if (!$values) {
            return null;
        }

        if (1 !== \count($values)) {
            throw new \InvalidArgumentException('Only one options can be configured at a time.');
        }

        $configuration = reset($values);
        switch (key($values)) {
            case 'elasticsearchOptions':
                return new Options($configuration['index'] ?? null, $configuration['type'] ?? null);
        }

        throw new \LogicException(sprintf('Unsupported "%s" state options.', key($values)));
    }
}
