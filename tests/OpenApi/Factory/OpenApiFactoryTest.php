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

namespace ApiPlatform\Tests\OpenApi\Factory;

use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\TypeFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
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
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\PathResolver\CustomOperationPathResolver;
use ApiPlatform\PathResolver\OperationPathResolver;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\Tests\Fixtures\DummyFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class OpenApiFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    private const OPERATION_FORMATS = [
        'input_formats' => ['jsonld' => ['application/ld+json']],
        'output_formats' => ['jsonld' => ['application/ld+json']],
    ];

    public function testInvoke(): void
    {
        $baseOperation = (new Operation())->withShortName('Dummy')->withDescription('This is a dummy')->withTypes(['http://schema.example.com/Dummy'])->withClass(Dummy::class)->withInputFormats(self::OPERATION_FORMATS['input_formats'])->withOutputFormats(self::OPERATION_FORMATS['output_formats'])->withOutput([
            'class' => OutputDto::class,
        ])->withPaginationClientItemsPerPage(true);
        $dummyResource = (new ApiResource())->withOperations(new Operations([
            'getDummyItem' => (new Get())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
            'putDummyItem' => (new Put())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
            'deleteDummyItem' => (new Delete())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]),
            'customDummyItem' => (new Operation())->withMethod(Operation::METHOD_HEAD)->withUriTemplate('/foo/{id}')->withOperation($baseOperation)->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])])->withOpenapiContext([
                'x-visibility' => 'hide',
                'description' => 'Custom description',
                'parameters' => [
                    ['description' => 'Test parameter', 'name' => 'param', 'in' => 'path', 'required' => true],
                    ['description' => 'Replace parameter', 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'uuid']],
                ],
                'tags' => ['Dummy', 'Profile'],
                'responses' => [
                    '202' => [
                        'description' => 'Success',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Dummy'],
                            ],
                        ],
                        'headers' => [
                            'Foo' => ['description' => 'A nice header', 'schema' => ['type' => 'integer']],
                        ],
                        'links' => [
                            'Foo' => ['$ref' => '#/components/schemas/Dummy'],
                        ],
                    ],
                    '205' => [],
                ],
                'requestBody' => [
                    'required' => true,
                    'description' => 'Custom request body',
                    'content' => [
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
                    ],
                ],
                'externalDocs' => ['url' => 'http://schema.example.com/Dummy', 'description' => 'See also'],
            ]
            ),
            'custom-http-verb' => (new Operation())->withMethod('TEST')->withOperation($baseOperation),
            'withRoutePrefix' => (new GetCollection())->withUriTemplate('/dummies')->withRoutePrefix('/prefix')->withOperation($baseOperation),
            'formatsDummyItem' => (new Put())->withOperation($baseOperation)->withUriTemplate('/formatted/{id}')->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])])->withInputFormats(['json' => ['application/json'], 'csv' => ['text/csv']])->withOutputFormats(['json' => ['application/json'], 'csv' => ['text/csv']]),
            'getDummyCollection' => (new GetCollection())->withUriTemplate('/dummies')->withOpenApiContext([
                'parameters' => [
                    ['description' => 'Test modified collection page number', 'name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 1], 'allowEmptyValue' => true],
                ],
            ])->withOperation($baseOperation),
            'postDummyCollection' => (new Post())->withUriTemplate('/dummies')->withOperation($baseOperation),
            // Filtered
            'filteredDummyCollection' => (new Get())->withUriTemplate('/filtered')->withCollection(true)->withFilters(['f1', 'f2', 'f3', 'f4', 'f5'])->withOperation($baseOperation),
            // Paginated
            'paginatedDummyCollection' => (new Get())->withUriTemplate('/paginated')
                                               ->withCollection(true)
                                           ->withPaginationClientEnabled(true)
                                           ->withPaginationClientItemsPerPage(true)
                                           ->withPaginationItemsPerPage(20)
                                       ->withPaginationMaximumItemsPerPage(80)
                                               ->withOperation($baseOperation),
        ])
        );

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->shouldBeCalled()->willReturn(new ResourceNameCollection([Dummy::class]));

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [$dummyResource]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate', 'enum']));
        $propertyNameCollectionFactoryProphecy->create(OutputDto::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate', 'enum']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('This is an id.')->withReadable(true)->withWritable(false)->withIdentifier(true)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is a name.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withRequired(false)->withIdentifier(false)->withSchema(['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$'])
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is an initializable but not writable property.')->withReadable(true)->withWritable(false)->withReadableLink(true)->withWritableLink(true)->withRequired(false)->withIdentifier(false)->withInitializable(true)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class)])->withDescription('This is a \DateTimeInterface object.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withRequired(false)->withIdentifier(false)
        );
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'enum', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is an enum.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withRequired(false)->withIdentifier(false)->withOpenapiContext(['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withDescription('This is an id.')->withReadable(true)->withWritable(false)->withIdentifier(true)
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'name', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is a name.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withRequired(false)->withIdentifier(false)->withSchema(['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$'])
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'description', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is an initializable but not writable property.')->withReadable(true)->withWritable(false)->withReadableLink(true)->withWritableLink(true)->withInitializable(true)
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class)])->withDescription('This is a \DateTimeInterface object.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)
        );
        $propertyMetadataFactoryProphecy->create(OutputDto::class, 'enum', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('This is an enum.')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withOpenapiContext(['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one'])
        );

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filters = [
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'openapi' => ['example' => 'bar', 'deprecated' => true, 'allowEmptyValue' => true, 'allowReserved' => true, 'explode' => true],
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

        $typeFactory = new TypeFactory();
        $schemaFactory = new SchemaFactory($typeFactory, $resourceCollectionMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new CamelCaseToSnakeCaseNameConverter());
        $typeFactory->setSchemaFactory($schemaFactory);

        $factory = new OpenApiFactory(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceCollectionMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $schemaFactory,
            $typeFactory,
            $operationPathResolver,
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
            ]),
            new PaginationOptions(true, 'page', true, 'itemsPerPage', true, 'pagination')
        );

        $dummySchema = new Schema('openapi');
        $dummySchema->setDefinitions(new \ArrayObject([
            'type' => 'object',
            'description' => 'This is a dummy',
            'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
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
                    'type' => 'string',
                    'description' => 'This is a \DateTimeInterface object.',
                    'format' => 'date-time',
                    'nullable' => true,
                ]),
                'enum' => new \ArrayObject([
                    'type' => 'string',
                    'enum' => ['one', 'two'],
                    'example' => 'one',
                    'description' => 'This is an enum.',
                ]),
            ],
        ]));

        $openApi = $factory(['base_url' => '/app_dev.php/']);

        $this->assertInstanceOf(OpenApi::class, $openApi);
        $this->assertEquals($openApi->getInfo(), new Model\Info('Test API', '1.2.3', 'This is a test API.'));
        $this->assertEquals($openApi->getServers(), [new Model\Server('/app_dev.php/')]);

        $components = $openApi->getComponents();
        $this->assertInstanceOf(Model\Components::class, $components);

        $this->assertEquals($components->getSchemas(), new \ArrayObject(['Dummy' => $dummySchema->getDefinitions(), 'Dummy.OutputDto' => $dummySchema->getDefinitions()]));

        $this->assertEquals($components->getSecuritySchemes(), new \ArrayObject([
            'oauth' => new Model\SecurityScheme('oauth2', 'OAuth 2.0 authorization code Grant', null, null, null, null, new Model\OAuthFlows(null, null, null, new Model\OAuthFlow('/oauth/v2/auth', '/oauth/v2/token', '/oauth/v2/refresh', new \ArrayObject(['scope param'])))),
            'header' => new Model\SecurityScheme('apiKey', 'Value for the Authorization header parameter.', 'Authorization', 'header'),
            'query' => new Model\SecurityScheme('apiKey', 'Value for the key query parameter.', 'key', 'query'),
        ]));

        $this->assertSame([
            ['oauth' => []],
            ['header' => []],
            ['query' => []],
        ], $openApi->getSecurity());

        $paths = $openApi->getPaths();
        $dummiesPath = $paths->getPath('/dummies');
        $this->assertNotNull($dummiesPath);
        foreach (['Put', 'Head', 'Trace', 'Delete', 'Options', 'Patch'] as $method) {
            $this->assertNull($dummiesPath->{'get'.$method}());
        }

        $this->assertEquals(new Model\Operation(
            'getDummyCollection',
            ['Dummy'],
            [
                '200' => new Model\Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new Model\MediaType(new \ArrayObject(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ]))),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Model\Parameter('page', 'query', 'Test modified collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Model\Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Model\Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ]
        ), $dummiesPath->getGet());

        $this->assertEquals(new Model\Operation(
            'postDummyCollection',
            ['Dummy'],
            [
                '201' => new Model\Response(
                    'Dummy resource created',
                    new \ArrayObject([
                        'application/ld+json' => new Model\MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$response.body#/id']), null, 'This is a dummy')])
                ),
                '400' => new Model\Response('Invalid input'),
                '422' => new Model\Response('Unprocessable entity'),
            ],
            'Creates a Dummy resource.',
            'Creates a Dummy resource.',
            null,
            [],
            new Model\RequestBody(
                'The new Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new Model\MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy']))),
                ]),
                true
            )
        ), $dummiesPath->getPost());

        $dummyPath = $paths->getPath('/dummies/{id}');
        $this->assertNotNull($dummyPath);
        foreach (['Post', 'Head', 'Trace', 'Options', 'Patch'] as $method) {
            $this->assertNull($dummyPath->{'get'.$method}());
        }

        $this->assertEquals(new Model\Operation(
            'getDummyItem',
            ['Dummy'],
            [
                '200' => new Model\Response(
                    'Dummy resource',
                    new \ArrayObject([
                        'application/ld+json' => new Model\MediaType(new \ArrayObject(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto']))),
                    ])
                ),
                '404' => new Model\Response('Resource not found'),
            ],
            'Retrieves a Dummy resource.',
            'Retrieves a Dummy resource.',
            null,
            [new Model\Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])]
        ), $dummyPath->getGet());

        $this->assertEquals(new Model\Operation(
            'putDummyItem',
            ['Dummy'],
            [
                '200' => new Model\Response(
                    'Dummy resource updated',
                    new \ArrayObject([
                        'application/ld+json' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$request.path.id']), null, 'This is a dummy')])
                ),
                '400' => new Model\Response('Invalid input'),
                '422' => new Model\Response('Unprocessable entity'),
                '404' => new Model\Response('Resource not found'),
            ],
            'Replaces the Dummy resource.',
            'Replaces the Dummy resource.',
            null,
            [new Model\Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])],
            new Model\RequestBody(
                'The updated Dummy resource',
                new \ArrayObject([
                    'application/ld+json' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            )
        ), $dummyPath->getPut());

        $this->assertEquals(new Model\Operation(
            'deleteDummyItem',
            ['Dummy'],
            [
                '204' => new Model\Response('Dummy resource deleted'),
                '404' => new Model\Response('Resource not found'),
            ],
            'Removes the Dummy resource.',
            'Removes the Dummy resource.',
            null,
            [new Model\Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])]
        ), $dummyPath->getDelete());

        $customPath = $paths->getPath('/foo/{id}');
        $this->assertEquals(new Model\Operation(
            'customDummyItem',
            ['Dummy', 'Profile'],
            [
                '202' => new Model\Response('Success', new \ArrayObject([
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                    ],
                ]), new \ArrayObject([
                    'Foo' => ['description' => 'A nice header', 'schema' => ['type' => 'integer']],
                ]), new \ArrayObject([
                    'Foo' => ['$ref' => '#/components/schemas/Dummy'],
                ])),
                '205' => new Model\Response(),
                '404' => new Model\Response('Resource not found'),
            ],
            'Dummy',
            'Custom description',
            new ExternalDocumentation('See also', 'http://schema.example.com/Dummy'),
            [new Model\Parameter('param', 'path', 'Test parameter', true), new Model\Parameter('id', 'path', 'Replace parameter', true, false, false, ['type' => 'string', 'format' => 'uuid'])],
            new Model\RequestBody('Custom request body', new \ArrayObject([
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
        $this->assertEquals(new Model\Operation(
            'formatsDummyItem',
            ['Dummy'],
            [
                '200' => new Model\Response(
                    'Dummy resource updated',
                    new \ArrayObject([
                        'application/json' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                        'text/csv' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy.OutputDto'])),
                    ]),
                    null,
                    new \ArrayObject(['getDummyItem' => new Model\Link('getDummyItem', new \ArrayObject(['id' => '$request.path.id']), null, 'This is a dummy')])
                ),
                '400' => new Model\Response('Invalid input'),
                '422' => new Model\Response('Unprocessable entity'),
                '404' => new Model\Response('Resource not found'),
            ],
            'Replaces the Dummy resource.',
            'Replaces the Dummy resource.',
            null,
            [new Model\Parameter('id', 'path', 'Dummy identifier', true, false, false, ['type' => 'string'])],
            new Model\RequestBody(
                'The updated Dummy resource',
                new \ArrayObject([
                    'application/json' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                    'text/csv' => new Model\MediaType(new \ArrayObject(['$ref' => '#/components/schemas/Dummy'])),
                ]),
                true
            )
        ), $formattedPath->getPut());

        $filteredPath = $paths->getPath('/filtered');
        $this->assertEquals(new Model\Operation(
            'filteredDummyCollection',
            ['Dummy'],
            [
                '200' => new Model\Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new Model\MediaType(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ])),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Model\Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Model\Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 30,
                    'minimum' => 0,
                ]),
                new Model\Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
                new Model\Parameter('name', 'query', '', true, true, true, [
                    'type' => 'string',
                ], 'form', true, true, 'bar'),
                new Model\Parameter('ha', 'query', '', false, false, true, [
                    'type' => 'integer',
                ]),
                new Model\Parameter('toto', 'query', '', true, false, true, [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ], 'deepObject', true),
                new Model\Parameter('order[name]', 'query', '', false, false, true, [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                ]),
            ]
        ), $filteredPath->getGet());

        $paginatedPath = $paths->getPath('/paginated');
        $this->assertEquals(new Model\Operation(
            'paginatedDummyCollection',
            ['Dummy'],
            [
                '200' => new Model\Response('Dummy collection', new \ArrayObject([
                    'application/ld+json' => new Model\MediaType(new \ArrayObject([
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/Dummy.OutputDto'],
                    ])),
                ])),
            ],
            'Retrieves the collection of Dummy resources.',
            'Retrieves the collection of Dummy resources.',
            null,
            [
                new Model\Parameter('page', 'query', 'The collection page number', false, false, true, [
                    'type' => 'integer',
                    'default' => 1,
                ]),
                new Model\Parameter('itemsPerPage', 'query', 'The number of items per page', false, false, true, [
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 0,
                    'maximum' => 80,
                ]),
                new Model\Parameter('pagination', 'query', 'Enable or disable pagination', false, false, true, [
                    'type' => 'boolean',
                ]),
            ]
        ), $paginatedPath->getGet());
    }
}
