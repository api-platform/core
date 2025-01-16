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

namespace ApiPlatform\OpenApi\Tests\Factory;

use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\OAuthFlow;
use ApiPlatform\OpenApi\Model\OAuthFlows;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Tests\Fixtures\Dummy;
use ApiPlatform\OpenApi\Tests\Fixtures\DummyErrorResource;
use ApiPlatform\OpenApi\Tests\Fixtures\DummyFilter;
use ApiPlatform\OpenApi\Tests\Fixtures\Issue6872\Diamond;
use ApiPlatform\OpenApi\Tests\Fixtures\OutputDto;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithParameter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class OpenApiFactoryTest extends TestCase
{
    use ProphecyTrait;

    private const OPERATION_FORMATS = [
        'input_formats' => ['jsonld' => ['application/ld+json']],
        'output_formats' => ['jsonld' => ['application/ld+json']],
    ];

    public function testInvoke(): void
    {
        $baseOperation = (new HttpOperation())->withTypes(['http://schema.example.com/Dummy'])->withInputFormats(self::OPERATION_FORMATS['input_formats'])->withOutputFormats(self::OPERATION_FORMATS['output_formats'])->withClass(Dummy::class)->withOutput([
            'class' => OutputDto::class,
        ])->withPaginationClientItemsPerPage(true)->withShortName('Dummy')->withDescription('This is a dummy');

        $dummyResourceWebhook = (new ApiResource())->withOperations(new Operations([
            'dummy webhook' => (new Get())->withUriTemplate('/dummy/{id}')->withShortName('short')->withOpenapi(new Webhook('first webhook')),
            'an other dummy webhook' => (new Post())->withUriTemplate('/dummies')->withShortName('short something')->withOpenapi(new Webhook('happy webhook', new Model\PathItem(post: new Operation(
                summary: 'well...',
                description: 'I dont\'t know what to say',
            )))),
        ]));

        $dummyResource = (new ApiResource())->withOperations(
            new Operations([
                'ignored' => new NotExposed(),
                'ignoredWithUriTemplate' => (new NotExposed())->withUriTemplate('/dummies/{id}'),
                'getDummyItem' => (new Get())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
                'putDummyItem' => (new Put())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
                'deleteDummyItem' => (new Delete())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
                'customDummyItem' => (new HttpOperation())->withMethod('HEAD')->withUriTemplate('/foo/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])])->withOpenapi(new OpenApiOperation(
                    tags: ['Dummy', 'Profile'],
                    responses: [
                        '202' => new OpenApiResponse(
                            description: 'Success',
                            content: new \ArrayObject([
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ]),
                            headers: new \ArrayObject([
                                'Foo' => ['description' => 'A nice header', 'schema' => ['type' => 'integer']],
                            ]),
                            links: new \ArrayObject([
                                'Foo' => ['$ref' => '#/components/schemas/Dummy'],
                            ]),
                        ),
                        '205' => new OpenApiResponse(),
                    ],
                    description: 'Custom description',
                    externalDocs: new ExternalDocumentation(
                        description: 'See also',
                        url: 'http://schema.example.com/Dummy',
                    ),
                    parameters: [
                        new Parameter(
                            name: 'param',
                            in: 'path',
                            description: 'Test parameter',
                            required: true,
                        ),
                        new Parameter(
                            name: 'id',
                            in: 'path',
                            description: 'Replace parameter',
                            required: true,
                            schema: ['type' => 'string', 'format' => 'uuid'],
                        ),
                    ],
                    requestBody: new RequestBody(
                        description: 'Custom request body',
                        content: new \ArrayObject([
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'file' => [
                                            'type' => 'string',
                                            'format' => 'binary',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                        required: true,
                    ),
                    extensionProperties: ['x-visibility' => 'hide'],
                )),
                'custom-http-verb' => (new HttpOperation())->withMethod('TEST')->withOperation($baseOperation),
                'withRoutePrefix' => (new GetCollection())->withUriTemplate('/dummies')->withRoutePrefix('/prefix')->withOperation($baseOperation),
                'formatsDummyItem' => (new Put())->withOperation($baseOperation)->withUriTemplate('/formatted/{id}')->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])])->withInputFormats(['json' => ['application/json'], 'csv' => ['text/csv']])->withOutputFormats(['json' => ['application/json'], 'csv' => ['text/csv']]),
                'getDummyCollection' => (new GetCollection())->withUriTemplate('/dummies')->withOpenapi(new OpenApiOperation(
                    parameters: [
                        new Parameter(
                            name: 'page',
                            in: 'query',
                            description: 'Test modified collection page number',
                            required: false,
                            allowEmptyValue: true,
                            schema: ['type' => 'integer', 'default' => 1],
                        ),
                    ],
                ))->withOperation($baseOperation),
                'postDummyCollection' => (new Post())->withUriTemplate('/dummies')->withOperation($baseOperation),
                // Filtered
                'filteredDummyCollection' => (new GetCollection())->withUriTemplate('/filtered')->withFilters(['f1', 'f2', 'f3', 'f4', 'f5'])->withOperation($baseOperation),
                // Paginated
                'paginatedDummyCollection' => (new GetCollection())->withUriTemplate('/paginated')
                    ->withPaginationClientEnabled(true)
                    ->withPaginationClientItemsPerPage(true)
                    ->withPaginationItemsPerPage(20)
                    ->withPaginationMaximumItemsPerPage(80)
                    ->withOperation($baseOperation),
                'postDummyCollectionWithRequestBody' => (new Post())->withUriTemplate('/dummiesRequestBody')->withOperation($baseOperation)->withOpenapi(new OpenApiOperation(
                    requestBody: new RequestBody(
                        description: 'List of Ids',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'ids' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string'],
                                            'example' => [
                                                '1e677e04-d461-4389-bedc-6d1b665cc9d6',
                                                '01111b43-f53a-4d50-8639-148850e5da19',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                    ),
                )),
                'postDummyCollectionWithRequestBodyWithoutContent' => (new Post())->withUriTemplate('/dummiesRequestBodyWithoutContent')->withOperation($baseOperation)->withOpenapi(new OpenApiOperation(
                    requestBody: new RequestBody(
                        description: 'Extended description for the new Dummy resource',
                    ),
                )),
                'putDummyItemWithResponse' => (new Put())->withUriTemplate('/dummyitems/{id}')->withOperation($baseOperation)->withOpenapi(new OpenApiOperation(
                    responses: [
                        '200' => new OpenApiResponse(
                            description: 'Success',
                            content: new \ArrayObject([
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ]),
                            headers: new \ArrayObject([
                                'API_KEY' => ['description' => 'Api Key', 'schema' => ['type' => 'string']],
                            ]),
                            links: new \ArrayObject([
                                'link' => ['$ref' => '#/components/schemas/Dummy'],
                            ]),
                        ),
                        '400' => new OpenApiResponse(
                            description: 'Error',
                        ),
                    ],
                )),
                'getDummyItemImageCollection' => (new GetCollection())->withUriTemplate('/dummyitems/{id}/images')->withOperation($baseOperation)->withOpenapi(new OpenApiOperation(
                    responses: [
                        '200' => new OpenApiResponse(
                            description: 'Success',
                        ),
                    ],
                )),
                'postDummyItemWithResponse' => (new Post())->withUriTemplate('/dummyitems')->withOperation($baseOperation)->withOpenapi(new OpenApiOperation(
                    responses: [
                        '201' => new OpenApiResponse(
                            description: 'Created',
                            content: new \ArrayObject([
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ]),
                            headers: new \ArrayObject([
                                'API_KEY' => ['description' => 'Api Key', 'schema' => ['type' => 'string']],
                            ]),
                            links: new \ArrayObject([
                                'link' => ['$ref' => '#/components/schemas/Dummy'],
                            ]),
                        ),
                        '400' => new OpenApiResponse(
                            description: 'Error',
                        ),
                    ],
                )),
                'postDummyItemWithoutInput' => (new Post())->withUriTemplate('/dummyitem/noinput')->withOperation($baseOperation)->withInput(false),
                'getDummyCollectionWithErrors' => (new GetCollection())->withUriTemplate('erroredDummies')->withErrors([DummyErrorResource::class])->withOperation($baseOperation),
            ])
        );

        $baseOperation = (new HttpOperation())->withTypes(['http://schema.example.com/Dummy'])->withInputFormats(self::OPERATION_FORMATS['input_formats'])->withOutputFormats(self::OPERATION_FORMATS['output_formats'])->withClass(Dummy::class)->withShortName('Parameter')->withDescription('This is a dummy');
        $parameterResource = (new ApiResource())->withOperations(new Operations([
            'uriVariableSchema' => (new Get(uriTemplate: '/uri_variable_uuid', uriVariables: ['id' => new Link(schema: ['type' => 'string', 'format' => 'uuid'], description: 'hello', required: true, openApi: new Parameter('id', 'path', allowEmptyValue: true))]))->withOperation($baseOperation),
            'parameters' => (new Put(uriTemplate: '/parameters', parameters: [
                'foo' => new HeaderParameter(description: 'hi', schema: ['type' => 'string', 'format' => 'uuid']),
            ]))->withOperation($baseOperation),
        ]));

        $diamondResource = (new ApiResource())
            ->withOperations(new Operations([
                'getDiamondCollection' => (new GetCollection(uriTemplate: '/diamonds'))
                    ->withSecurity("is_granted('ROLE_USER')")
                    ->withOperation($baseOperation),
                'putDiamond' => (new Put(uriTemplate: '/diamond/{id}'))
                    ->withOperation($baseOperation),
            ]));

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->shouldBeCalled()->willReturn(new ResourceNameCollection([Dummy::class, WithParameter::class, Diamond::class]));

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [$dummyResource, $dummyResourceWebhook]));
        $resourceCollectionMetadataFactoryProphecy->create(DummyErrorResource::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(DummyErrorResource::class, [new ApiResource(operations: [new ErrorOperation(name: 'err', description: 'nice one!')])]));
        $resourceCollectionMetadataFactoryProphecy->create(WithParameter::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(WithParameter::class, [$parameterResource]));
        $resourceCollectionMetadataFactoryProphecy->create(Diamond::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Diamond::class, [$diamondResource]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate', 'enum']));
        $propertyNameCollectionFactoryProphecy->create(DummyErrorResource::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['type', 'title', 'status', 'detail', 'instance']));
        $propertyNameCollectionFactoryProphecy->create(OutputDto::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate', 'enum']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withDescription('This is an id.')
                ->withReadable(true)
                ->withWritable(false)
                ->withIdentifier(true)
                ->withSchema(['type' => 'integer', 'readOnly' => true, 'description' => 'This is an id.'])
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is a name.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withRequired(false)
                ->withIdentifier(false)
                ->withSchema(['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$', 'description' => 'This is a name.', 'type' => 'string'])
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an initializable but not writable property.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withRequired(false)
                ->withIdentifier(false)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an initializable but not writable property.'])
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class)])
                ->withDescription('This is a \DateTimeInterface object.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withRequired(false)
                ->withIdentifier(false)
                ->withSchema(['type' => ['string', 'null'], 'description' => 'This is a \DateTimeInterface object.', 'format' => 'date-time'])
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'enum', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an enum.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withRequired(false)
                ->withIdentifier(false)
                ->withSchema(['type' => 'string', 'description' => 'This is an enum.'])
                ->withOpenapiContext(['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withDescription('This is an id.')
                ->withReadable(true)
                ->withWritable(false)
                ->withIdentifier(true)
                ->withSchema(['type' => 'integer', 'description' => 'This is an id.', 'readOnly' => true])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'name', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is a name.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withRequired(false)
                ->withIdentifier(false)
                ->withSchema(['type' => 'string', 'description' => 'This is a name.', 'minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'description', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an initializable but not writable property.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an initializable but not writable property.'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class)])
                ->withDescription('This is a \DateTimeInterface object.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withSchema(['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'This is a \DateTimeInterface object.'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'enum', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an enum.')
                ->withReadable(true)
                ->withWritable(true)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an enum.'])
                ->withOpenapiContext(['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one'])
        );
        $propertyMetadataFactoryProphecy->create(DummyErrorResource::class, 'type', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an error type.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an error type.'])
        );
        $propertyMetadataFactoryProphecy->create(DummyErrorResource::class, 'title', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an error title.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an error title.'])
        );
        $propertyMetadataFactoryProphecy->create(DummyErrorResource::class, 'status', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withDescription('This is an error status.')
                ->withReadable(true)
                ->withWritable(false)
                ->withIdentifier(true)
                ->withSchema(['type' => 'integer', 'description' => 'This is an error status.', 'readOnly' => true])
        );
        $propertyMetadataFactoryProphecy->create(DummyErrorResource::class, 'detail', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an error detail.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an error detail.'])
        );
        $propertyMetadataFactoryProphecy->create(DummyErrorResource::class, 'instance', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withDescription('This is an error instance.')
                ->withReadable(true)
                ->withWritable(false)
                ->withReadableLink(true)
                ->withWritableLink(true)
                ->withInitializable(true)
                ->withSchema(['type' => 'string', 'description' => 'This is an error instance.'])
        );

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filters = [
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'openapi' => new Parameter(in: 'query', name: 'name', example: 'bar', deprecated: true, allowEmptyValue: true, allowReserved: true, explode: true),
            ]]),
            'f2' => new DummyFilter(['ha' => [
                'property' => 'foo',
                'type' => 'int',
                'required' => false,
                'strategy' => 'partial',
            ]]),
            'f3' => new DummyFilter(['toto' => [
                'property' => 'name',
                'type' => 'array',
                'is_collection' => true,
                'required' => true,
                'strategy' => 'exact',
            ]]),
            'f4' => new DummyFilter(['order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                ],
            ]]),
        ];

        foreach ($filters as $filterId => $filter) {
            $filterLocatorProphecy->has($filterId)->willReturn(true)->shouldBeCalled();
            $filterLocatorProphecy->get($filterId)->willReturn($filter)->shouldBeCalled();
        }

        $filterLocatorProphecy->has('f5')->willReturn(false)->shouldBeCalled();

        $resourceCollectionMetadataFactory = $resourceCollectionMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $definitionNameFactory = new DefinitionNameFactory([]);

        $schemaFactory = new SchemaFactory(
            resourceMetadataFactory: $resourceCollectionMetadataFactory,
            propertyNameCollectionFactory: $propertyNameCollectionFactory,
            propertyMetadataFactory: $propertyMetadataFactory,
            nameConverter: new CamelCaseToSnakeCaseNameConverter(),
            definitionNameFactory: $definitionNameFactory,
        );

        $factory = new OpenApiFactory(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceCollectionMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $schemaFactory,
            $filterLocatorProphecy->reveal(),
            [],
            new Options('Test API', 'This is a test API.', '1.2.3', true, 'oauth2', 'authorizationCode', '/oauth/v2/token', '/oauth/v2/auth', '/oauth/v2/refresh', ['scope param'], [
                'header' => [
                    'type' => 'header',
                    'name' => 'Authorization',
                ],
                'query' => [
                    'type' => 'query',
                    'name' => 'key',
                ],
            ], null, null, null, null, null, null, true, true, [
                'bearer' => [
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
                'basic' => [
                    'scheme' => 'basic',
                ],
            ]),
            new PaginationOptions(true, 'page', true, 'itemsPerPage', true, 'pagination')
        );

        $dummySchema = new Schema('openapi');
        $dummySchema->setDefinitions(new \ArrayObject([
            'type' => 'object',
            'description' => 'This is a dummy',
            'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
            'deprecated' => false,
            'properties' => [
                'id' => new \ArrayObject([
                    'type' => 'integer',
                    'description' => 'This is an id.',
                    'readOnly' => true,
                ]),
                'name' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is a name.',
                    'minLength' => 3,
                    'maxLength' => 20,
                    'pattern' => '^dummyPattern$',
                ]),
                'description' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is an initializable but not writable property.',
                ]),
                'dummy_date' => new \ArrayObject([
                    'type' => ['string', 'null'],
                    'description' => 'This is a \DateTimeInterface object.',
                    'format' => 'date-time',
                ]),
                'enum' => new \ArrayObject([
                    'type' => 'string',
                    'enum' => ['one', 'two'],
                    'example' => 'one',
                    'description' => 'This is an enum.',
                ]),
            ],
        ]));
        $dummyErrorSchema = new Schema('openapi');
        $dummyErrorSchema->setDefinitions(new \ArrayObject([
            'type' => 'object',
            'description' => 'nice one!',
            'deprecated' => false,
            'properties' => [
                'type' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is an error type.',
                ]),
                'title' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is an error title.',
                ]),
                'status' => new \ArrayObject([
                    'type' => 'integer',
                    'description' => 'This is an error status.',
                    'readOnly' => true,
                ]),
                'detail' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is an error detail.',
                ]),
                'instance' => new \ArrayObject([
                    'type' => 'string',
                    'description' => 'This is an error instance.',
                ]),
            ],
        ]));

        $openApi = $factory(['base_url' => '/app_dev.php/']);

        $this->assertInstanceOf(OpenApi::class, $openApi);
        $this->assertEquals($openApi->getInfo(), new Info('Test API', '1.2.3', 'This is a test API.'));
        $this->assertEquals($openApi->getServers(), [new Server('/app_dev.php/')]);

        $webhooks = $openApi->getWebhooks();
        $this->assertCount(2, $webhooks);

        $firstOperationWebhook = $webhooks['first webhook'];
        $secondOperationWebhook = $webhooks['happy webhook'];
        $this->assertSame('dummy webhook', $firstOperationWebhook->getGet()->getOperationId());
        $this->assertSame('an other dummy webhook', $secondOperationWebhook->getPost()->getOperationId());
        $this->assertSame('I dont\'t know what to say', $secondOperationWebhook->getPost()->getDescription());
        $this->assertSame('well...', $secondOperationWebhook->getPost()->getSummary());

        $components = $openApi->getComponents();
        $this->assertInstanceOf(Components::class, $components);

        $parameterSchema = $dummySchema->getDefinitions();
        $this->assertEquals($components->getSchemas(), new \ArrayObject([
            'Dummy' => $dummySchema->getDefinitions(),
            'Dummy.OutputDto' => $dummySchema->getDefinitions(),
            'DummyErrorResource' => $dummyErrorSchema->getDefinitions(),
            'Parameter' => $parameterSchema,
        ]));

        $this->assertEquals($components->getSecuritySchemes(), new \ArrayObject([
            'oauth' => new SecurityScheme('oauth2', 'OAuth 2.0 authorization code Grant', null, null, null, null, new OAuthFlows(null, null, null, new OAuthFlow('/oauth/v2/auth', '/oauth/v2/token', '/oauth/v2/refresh', new \ArrayObject(['scope param'])))),
            'header' => new SecurityScheme('apiKey', 'Value for the Authorization header parameter.', 'Authorization', 'header'),
            'query' => new SecurityScheme('apiKey', 'Value for the key query parameter.', 'key', 'query'),
            'bearer' => new SecurityScheme('http', 'Value for the http bearer parameter.', null, null, 'bearer', 'JWT'),
            'basic' => new SecurityScheme('http', 'Value for the http basic parameter.', null, null, 'basic', null),
        ]));

        $this->assertEquals([
            ['oauth' => []],
            ['header' => []],
            ['query' => []],
            ['bearer' => []],
            ['basic' => []],
        ], $openApi->getSecurity());

        $paths = $openApi->getPaths();
        $dummiesPath = $paths->getPath('/dummies');
        $this->assertNotNull($dummiesPath);
        foreach (['Put', 'Head', 'Trace', 'Delete', 'Options', 'Patch'] as $method) {
            $this->assertNull($dummiesPath->{'get'.$method}());
        }

        $this->assertEquals(new Operation(
            'getDummyCollection',
            ['Dummy'],
            [
                '200' => new Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ]))),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Parameter('page', 'query', 'Test modified collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ]
        ), $dummiesPath->getGet());

        $this->assertEquals(new Operation(
            'postDummyCollection',
            ['Dummy'],
            [
                '201' => new Response(
                    'Dummy resource created',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$response.body#/id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            new RequestBody(
                'The new Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy']))),
                ]),
                true
            )
        ), $dummiesPath->getPost());

        $dummyPath = $paths->getPath('/dummies/{id}');
        $this->assertNotNull($dummyPath);
        foreach (['Post', 'Head', 'Trace', 'Options', 'Patch'] as $method) {
            $this->assertNull($dummyPath->{'get'.$method}());
        }

        $this->assertEquals(new Operation(
            'getDummyItem',
            ['Dummy'],
            [
                '200' => new Response(
                    'Dummy resource',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ])
                ),
                '404' => new Response('Resource not found'),
            ],
            'Retrieves a Dummy resource.',
            'Retrieves a Dummy resource.',
            null,
            [new Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])]
        ), $dummyPath->getGet());

        $this->assertEquals(new Operation(
            'putDummyItem',
            ['Dummy'],
            [
                '200' => new Response(
                    'Dummy resource updated',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$request.path.id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
                '404' => new Response('Resource not found'),
            ],
            'Replaces the Dummy resource.',
            'Replaces the Dummy resource.',
            null,
            [new Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])],
            new RequestBody(
                'The updated Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            )
        ), $dummyPath->getPut());

        $this->assertEquals(new Operation(
            'deleteDummyItem',
            ['Dummy'],
            [
                '204' => new Response('Dummy resource deleted'),
                '404' => new Response('Resource not found'),
            ],
            'Removes the Dummy resource.',
            'Removes the Dummy resource.',
            null,
            [new Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])]
        ), $dummyPath->getDelete());

        $customPath = $paths->getPath('/foo/{id}');
        $this->assertEquals(new Operation(
            'customDummyItem',
            ['Dummy', 'Profile'],
            [
                '202' => new Response('Success', new \ArrayObject([
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                    ],
                ]), new \ArrayObject([
                    'Foo' => ['description' => 'A nice header', 'schema' => ['type' => 'integer']],
                ]), new \ArrayObject([
                    'Foo' => ['$ref' => '#/components/schemas/Dummy'],
                ])),
                '205' => new Response(),
                '404' => new Response('Resource not found'),
            ],
            'Dummy',
            'Custom description',
            new ExternalDocumentation('See also', 'http://schema.example.com/Dummy'),
            [new Parameter('param', 'path', 'Test parameter', true), new Parameter('id', 'path', 'Replace parameter', true, false, false, ['type' => 'string', 'format' => 'uuid'])],
            new RequestBody('Custom request body', new \ArrayObject([
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                        ],
                    ],
                ],
            ]), true),
            null,
            false,
            null,
            null,
            ['x-visibility' => 'hide']
        ), $customPath->getHead());

        $prefixPath = $paths->getPath('/prefix/dummies');
        $this->assertNotNull($prefixPath);

        $formattedPath = $paths->getPath('/formatted/{id}');
        $this->assertEquals(new Operation(
            'formatsDummyItem',
            ['Dummy'],
            [
                '200' => new Response(
                    'Dummy resource updated',
                    new \ArrayObject([
                        'application/json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                        'text/csv' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$request.path.id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
                '404' => new Response('Resource not found'),
            ],
            'Replaces the Dummy resource.',
            'Replaces the Dummy resource.',
            null,
            [new Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])],
            new RequestBody(
                'The updated Dummy resource',
                new \ArrayObject([
                    'application/json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                    'text/csv' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            )
        ), $formattedPath->getPut());

        $filteredPath = $paths->getPath('/filtered');
        $this->assertEquals(new Operation(
            'filteredDummyCollection',
            ['Dummy'],
            [
                '200' => new Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ])),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
                new Parameter('name', 'query', '', true, true, true, [
                    'type' => 'string',
                ], 'form', true, true, 'bar'),
                new Parameter('ha', 'query', '', false, false, false, [
                    'type' => 'integer',
                ]),
                new Parameter('toto', 'query', '', true, false, false, [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ], 'deepObject', true),
                new Parameter('order[name]', 'query', '', false, false, false, [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                ]),
            ],
            deprecated: false
        ), $filteredPath->getGet());

        $paginatedPath = $paths->getPath('/paginated');
        $this->assertEquals(new Operation(
            'paginatedDummyCollection',
            ['Dummy'],
            [
                '200' => new Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ])),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 0,
                    'maximum' => 80,
                ]),
                new Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ]
        ), $paginatedPath->getGet());

        $requestBodyPath = $paths->getPath('/dummiesRequestBody');
        $this->assertEquals(new Operation(
            'postDummyCollectionWithRequestBody',
            ['Dummy'],
            [
                '201' => new Response(
                    'Dummy resource created',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$response.body#/id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            new RequestBody(
                'List of Ids',
                new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'ids' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'example' => [
                                        '1e677e04-d461-4389-bedc-6d1b665cc9d6',
                                        '01111b43-f53a-4d50-8639-148850e5da19',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                false
            ),
            deprecated: false,
        ), $requestBodyPath->getPost());

        $requestBodyPath = $paths->getPath('/dummiesRequestBodyWithoutContent');
        $this->assertEquals(new Operation(
            'postDummyCollectionWithRequestBodyWithoutContent',
            ['Dummy'],
            [
                '201' => new Response(
                    'Dummy resource created',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$response.body#/id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            new RequestBody(
                'Extended description for the new Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy']))),
                ]),
                false
            ),
            deprecated: false,
        ), $requestBodyPath->getPost());

        $dummyItemPath = $paths->getPath('/dummyitems/{id}');
        $this->assertEquals(new Operation(
            'putDummyItemWithResponse',
            ['Dummy'],
            [
                '200' => new Response(
                    'Success',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Dummy'],
                        ],
                    ]),
                    new \ArrayObject([
                        'API_KEY' => ['description' => 'Api Key', 'schema' => ['type' => 'string']],
                    ]),
                    new \ArrayObject([
                        'link' => ['$ref' => '#/components/schemas/Dummy'],
                    ])
                ),
                '400' => new Response('Error'),
                '422' => new Response('Unprocessable entity'),
                '404' => new Response('Resource not found'),
            ],
            'Replaces the Dummy resource.',
            'Replaces the Dummy resource.',
            null,
            [],
            new RequestBody(
                'The updated Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            ),
            deprecated: false
        ), $dummyItemPath->getPut());

        $dummyItemPath = $paths->getPath('/dummyitems');
        $this->assertEquals(new Operation(
            'postDummyItemWithResponse',
            ['Dummy'],
            [
                '201' => new Response(
                    'Created',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Dummy'],
                        ],
                    ]),
                    new \ArrayObject([
                        'API_KEY' => ['description' => 'Api Key', 'schema' => ['type' => 'string']],
                    ]),
                    new \ArrayObject([
                        'link' => ['$ref' => '#/components/schemas/Dummy'],
                    ])
                ),
                '400' => new Response('Error'),
                '422' => new Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            new RequestBody(
                'The new Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            ),
            deprecated: false
        ), $dummyItemPath->getPost());

        $dummyItemPath = $paths->getPath('/dummyitems/{id}/images');

        $this->assertEquals(new Operation(
            'getDummyItemImageCollection',
            ['Dummy'],
            [
                '200' => new Response(
                    'Success'
                ),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ]
        ), $dummyItemPath->getGet());

        $emptyRequestBodyPath = $paths->getPath('/dummyitem/noinput');
        $this->assertEquals(new Operation(
            'postDummyItemWithoutInput',
            ['Dummy'],
            [
                '201' => new Response(
                    'Dummy resource created',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$response.body#/id']), null, 'This is a dummy')])
                ),
                '400' => new Response('Invalid input'),
                '422' => new Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            null
        ), $emptyRequestBodyPath->getPost());

        $parameter = $paths->getPath('/uri_variable_uuid')->getGet()->getParameters()[0];
        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertEquals(['type' => 'string', 'format' => 'uuid'], $parameter->getSchema());

        $parameter = $paths->getPath('/parameters')->getPut()->getParameters()[0];
        $this->assertEquals(['type' => 'string', 'format' => 'uuid'], $parameter->getSchema());
        $this->assertEquals('header', $parameter->getIn());
        $this->assertEquals('hi', $parameter->getDescription());

        $this->assertEquals(new Operation(
            'getDummyCollectionWithErrors',
            ['Dummy'],
            [
                '200' => new Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ]))),
                ])),
                '418' => new Response(
                    'A Teapot Exception',
                    new \ArrayObject([
                        'application/ld+json' => new MediaType(new \ArrayObject(new \ArrayObject([
                            '$ref' => '#/components/schemas/DummyErrorResource',
                        ]))),
                    ]),
                    links: new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(), null, 'This is a dummy')])
                ),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ],
            deprecated: false
        ), $paths->getPath('/erroredDummies')->getGet());

        $diamondsGetPath = $paths->getPath('/diamonds');
        $diamondGetOperation = $diamondsGetPath->getGet();
        $diamondGetResponses = $diamondGetOperation->getResponses();

        $this->assertNotNull($diamondGetOperation);
        $this->assertArrayHasKey('403', $diamondGetResponses);
        $this->assertSame('Forbidden', $diamondGetResponses['403']->getDescription());

        $diamondsPutPath = $paths->getPath('/diamond/{id}');
        $diamondPutOperation = $diamondsPutPath->getPut();
        $diamondPutResponses = $diamondPutOperation->getResponses();

        $this->assertNotNull($diamondPutOperation);
        $this->assertArrayNotHasKey('403', $diamondPutResponses);
    }
}
