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

namespace ApiPlatform\OpenApi\Tests\Serializer;

use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\TypeFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
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
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class OpenApiNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private const OPERATION_FORMATS = [
        'input_formats' => ['jsonld' => ['application/ld+json']],
        'output_formats' => ['jsonld' => ['application/ld+json']],
    ];

    public function testNormalizeWithSchemas(): void
    {
        $openApi = new OpenApi(new Info('My API', '1.0.0', 'An amazing API'), [new Server('https://example.com')], new Paths(), new Components(new \ArrayObject(['z' => [], 'b' => []])));
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $array = $normalizer->normalize($openApi);

        $this->assertSame(array_keys($array['components']['schemas']), ['b', 'z']);
    }

    public function testNormalizeWithEmptySchemas(): void
    {
        $openApi = new OpenApi(new Info('My API', '1.0.0', 'An amazing API'), [new Server('https://example.com')], new Paths(), new Components(new \ArrayObject()));
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $array = $normalizer->normalize($openApi);
        $this->assertCount(0, $array['components']['schemas']);
    }

    public function testNormalize(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->shouldBeCalled()->willReturn(new ResourceNameCollection([Dummy::class, 'Zorro']));
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate']));
        $propertyNameCollectionFactoryProphecy->create('Zorro', Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $baseOperation = (new HttpOperation())->withTypes(['http://schema.example.com/Dummy'])
                                              ->withInputFormats(self::OPERATION_FORMATS['input_formats'])->withOutputFormats(self::OPERATION_FORMATS['output_formats'])
                                              ->withClass(Dummy::class)
                                              ->withShortName('Dummy')
                                              ->withDescription('This is a dummy.');

        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations(
                [
                    'get' => (new Get())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation),
                    'put' => (new Put())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation),
                    'delete' => (new Delete())->withUriTemplate('/dummies/{id}')->withOperation($baseOperation),
                    'get_collection' => (new GetCollection())->withUriTemplate('/dummies')->withOperation($baseOperation),
                    'post' => (new Post())->withUriTemplate('/dummies')->withOpenapi(new OpenApiOperation(
                        security: [],
                        servers: ['url' => '/test'],
                    ))->withOperation($baseOperation),
                ]
            )),
        ]);

        $zorroBaseOperation = (new HttpOperation())
            ->withTypes(['http://schema.example.com/Zorro'])
            ->withInputFormats(self::OPERATION_FORMATS['input_formats'])->withOutputFormats(self::OPERATION_FORMATS['output_formats'])
            ->withClass('Zorro')
            ->withShortName('Zorro')
            ->withDescription('This is zorro.');

        $zorroMetadata = new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations(
                [
                    'get' => (new Get())->withUriTemplate('/zorros/{id}')->withOperation($zorroBaseOperation),
                    'get_collection' => (new GetCollection())->withUriTemplate('/zorros')->withOperation($zorroBaseOperation),
                ]
            )),
        ]);

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceCollectionMetadataFactoryProphecy->create('Zorro')->shouldBeCalled()->willReturn($zorroMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withDescription('This is an id.')
                ->withReadable(true)
                ->withWritable(false)
                ->withIdentifier(true)
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
                ->withSchema(['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$'])
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
        );

        $propertyMetadataFactoryProphecy->create('Zorro', 'id', Argument::any())->shouldBeCalled()->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withDescription('This is an id.')
                ->withReadable(true)
                ->withWritable(false)
                ->withIdentifier(true)
        );

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $resourceMetadataFactory = $resourceCollectionMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $typeFactory = new TypeFactory();
        $schemaFactory = new SchemaFactory($typeFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new CamelCaseToSnakeCaseNameConverter());
        $typeFactory->setSchemaFactory($schemaFactory);

        $factory = new OpenApiFactory(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $schemaFactory,
            $typeFactory,
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

        $openApi = $factory(['base_url' => '/app_dev.php/']);

        $pathItem = $openApi->getPaths()->getPath('/dummies/{id}');
        $operation = $pathItem->getGet();

        $openApi->getPaths()->addPath('/dummies/{id}', $pathItem->withGet(
            $operation->withParameters(array_merge(
                $operation->getParameters(),
                [new Parameter('fields', 'query', 'Fields to remove of the output')]
            ))
        ));

        $openApi = $openApi->withInfo((new Info('New Title', 'v2', 'Description of my custom API'))->withExtensionProperty('info-key', 'Info value'));
        $openApi = $openApi->withExtensionProperty('key', 'Custom x-key value');
        $openApi = $openApi->withExtensionProperty('x-value', 'Custom x-value value');

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $openApiAsArray = $normalizer->normalize($openApi);

        // Just testing normalization specifics
        $this->assertSame($openApiAsArray['x-key'], 'Custom x-key value');
        $this->assertSame($openApiAsArray['x-value'], 'Custom x-value value');
        $this->assertSame($openApiAsArray['info']['x-info-key'], 'Info value');
        $this->assertArrayNotHasKey('extensionProperties', $openApiAsArray);
        // this key is null, should not be in the output
        $this->assertArrayNotHasKey('termsOfService', $openApiAsArray['info']);
        $this->assertArrayNotHasKey('paths', $openApiAsArray['paths']);
        $this->assertArrayHasKey('/dummies/{id}', $openApiAsArray['paths']);
        $this->assertArrayNotHasKey('servers', $openApiAsArray['paths']['/dummies/{id}']['get']);
        $this->assertArrayNotHasKey('security', $openApiAsArray['paths']['/dummies/{id}']['get']);

        // Security can be disabled per-operation using an empty array
        $this->assertEquals([], $openApiAsArray['paths']['/dummies']['post']['security']);
        $this->assertEquals(['url' => '/test'], $openApiAsArray['paths']['/dummies']['post']['servers']);

        // Make sure things are sorted
        $this->assertSame(array_keys($openApiAsArray['paths']), ['/dummies', '/dummies/{id}', '/zorros', '/zorros/{id}']);
        // Test name converter doesn't rename this property
        $this->assertArrayHasKey('requestBody', $openApiAsArray['paths']['/dummies']['post']);
    }
}
