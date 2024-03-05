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

namespace ApiPlatform\Tests\Symfony\Bundle\ArgumentResolver;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Symfony\Bundle\ArgumentResolver\PayloadArgumentResolver;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceImplementation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PayloadArgumentResolverTest extends KernelTestCase
{
    use ProphecyTrait;

    public function testItSupportsRequestWithPayloadOfExpectedType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = self::createArgumentMetadata(ResourceImplementation::class);

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testItSupportsRequestWithPayloadOfChildType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = self::createArgumentMetadata(ResourceInterface::class);

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testItSupportsRequestWithDtoAsInput(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = self::createArgumentMetadata(NotResource::class);

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update_with_dto',
            'data' => new NotResource(),
        ]);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    /**
     * @dataProvider provideUnsupportedArguments
     */
    public function testItDoesNotSupportArgumentThatCannotBeResolved(ArgumentMetadata $argument): void
    {
        $resolver = $this->createArgumentResolver();

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertFalse($resolver->supports($request, $argument));
    }

    /**
     * @dataProvider provideUnsupportedRequests
     */
    public function testItDoesNotSupportRequestWithoutPayloadOfExpectedType(Request $request): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = self::createArgumentMetadata(ResourceInterface::class);

        $this->assertFalse($resolver->supports($request, $argument));
    }

    public function testItResolvesArgumentFromRequestWithDataOfExpectedType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = self::createArgumentMetadata(ResourceImplementation::class);

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertEquals(
            [$request->attributes->get('data')],
            iterator_to_array($resolver->resolve($request, $argument))
        );
    }

    public function testItResolvesArgumentFromRequestWithDataOfChildType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(ResourceInterface::class);

        $request = self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertEquals(
            [$request->attributes->get('data')],
            iterator_to_array($resolver->resolve($request, $argument))
        );
    }

    public static function provideUnsupportedRequests(): iterable
    {
        yield 'GET request' => [self::createRequest('GET', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'HEAD request' => [self::createRequest('HEAD', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'OPTIONS request' => [self::createRequest('OPTIONS', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'TRACE request' => [self::createRequest('TRACE', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'DELETE request' => [self::createRequest('DELETE', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'request without attributes' => [self::createRequest('PUT', [])];

        yield 'request on operation with deserialization disabled' => [self::createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_operation_name' => 'update_no_deserialize',
            'data' => new ResourceImplementation(),
        ])];
    }

    public static function provideUnsupportedArguments(): iterable
    {
        yield 'argument without type declaration' => [self::createArgumentMetadata()];
        yield 'variadic argument' => [self::createArgumentMetadata(ResourceImplementation::class, true)];
    }

    /**
     * @dataProvider provideIntegrationCases
     */
    public function testIntegration(Request $request, callable $controller, array $expectedArguments): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $argumentsResolver = $container->get('argument_resolver');

        $arguments = $argumentsResolver->getArguments($request, $controller);

        self::assertSame($expectedArguments, $arguments);
    }

    public static function provideIntegrationCases(): iterable
    {
        $resource = new ResourceImplementation();

        yield 'simple' => [
            self::createRequest('PUT', [
                '_api_resource_class' => ResourceImplementation::class,
                '_api_operation_name' => '_api_/resource_implementations{._format}_put',
                'data' => $resource,
            ]),
            static function (ResourceImplementation $payload): void {},
            [$resource],
        ];

        yield 'with another argument named $data' => [
            self::createRequest('PUT', [
                '_api_resource_class' => ResourceImplementation::class,
                '_api_operation_name' => '_api_/resource_implementations{._format}_put',
                'data' => $resource,
            ]),
            static function (ResourceImplementation $payload, $data): void {},
            [$resource, $resource],
        ];
    }

    private function createArgumentResolver(): PayloadArgumentResolver
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(ResourceImplementation::class)->willReturn(new ResourceMetadataCollection(ResourceImplementation::class, [
            (new ApiResource())->withShortName('ResourceImplementation')->withOperations(new Operations([
                'update' => new Put(),
                'update_no_deserialize' => (new Put())->withDeserialize(false),
                'update_with_dto' => (new Put())->withInput(['class' => NotResource::class, 'name' => 'NotResource']),
                'create' => new Post(),
            ])),
        ]));

        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder
            ->createFromRequest(
                Argument::type(Request::class),
                false,
                Argument::type('array')
            )
            ->will(function (array $arguments): array {
                /** @var Request $request */
                $request = $arguments[0];

                $context = [
                    'resource_class' => ResourceImplementation::class,
                ];

                if ('update_with_dto' === $request->attributes->get('_api_operation_name')) {
                    $context['input'] = ['class' => NotResource::class, 'name' => 'NotResource'];
                } else {
                    $context['input'] = null;
                }

                return $context;
            });

        return new PayloadArgumentResolver(
            $resourceMetadataFactory->reveal(),
            $serializerContextBuilder->reveal()
        );
    }

    private static function createRequest(string $method, array $attributes): Request
    {
        $request = Request::create('/foo', $method);
        $request->attributes->replace($attributes);

        return $request;
    }

    private static function createArgumentMetadata(?string $type = null, bool $isVariadic = false): ArgumentMetadata
    {
        return new ArgumentMetadata('foo', $type, $isVariadic, false, null);
    }
}
