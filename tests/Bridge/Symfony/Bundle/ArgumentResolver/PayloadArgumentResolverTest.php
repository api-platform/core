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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\ArgumentResolver;

use ApiPlatform\Core\Bridge\Symfony\Bundle\ArgumentResolver\PayloadArgumentResolver;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PayloadArgumentResolverTest extends KernelTestCase
{
    public function testItSupportsRequestWithPayloadOfExpectedType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(ResourceImplementation::class);

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testItSupportsRequestWithPayloadOfChildType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(ResourceInterface::class);

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testItSupportsRequestWithDtoAsInput(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(NotResource::class);

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update_with_dto',
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

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
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
        $argument = $this->createArgumentMetadata(ResourceInterface::class);

        $this->assertFalse($resolver->supports($request, $argument));
    }

    public function testItResolvesArgumentFromRequestWithDataOfExpectedType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(ResourceImplementation::class);

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertSame(
            [$request->attributes->get('data')],
            iterator_to_array($resolver->resolve($request, $argument))
        );
    }

    public function testItResolvesArgumentFromRequestWithDataOfChildType(): void
    {
        $resolver = $this->createArgumentResolver();
        $argument = $this->createArgumentMetadata(ResourceInterface::class);

        $request = $this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ]);

        $this->assertSame(
            [$request->attributes->get('data')],
            iterator_to_array($resolver->resolve($request, $argument))
        );
    }

    public function provideUnsupportedRequests(): iterable
    {
        yield 'GET request' => [$this->createRequest('GET', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'HEAD request' => [$this->createRequest('HEAD', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'OPTIONS request' => [$this->createRequest('OPTIONS', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'TRACE request' => [$this->createRequest('TRACE', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'DELETE request' => [$this->createRequest('DELETE', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'request without attributes' => [$this->createRequest('PUT', [])];

        yield 'request with receive=false' => [$this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => false,
            '_api_item_operation_name' => 'update',
            'data' => new ResourceImplementation(),
        ])];

        yield 'request on operation with deserialization disabled' => [$this->createRequest('PUT', [
            '_api_resource_class' => ResourceImplementation::class,
            '_api_receive' => true,
            '_api_item_operation_name' => 'update_no_deserialize',
            'data' => new ResourceImplementation(),
        ])];
    }

    public function provideUnsupportedArguments(): iterable
    {
        yield 'argument without type declaration' => [$this->createArgumentMetadata()];
        yield 'variadic argument' => [$this->createArgumentMetadata(ResourceImplementation::class, true)];
    }

    /**
     * @dataProvider provideIntegrationCases
     */
    public function testIntegration(Request $request, callable $controller, array $expectedArguments): void
    {
        self::bootKernel();

        $argumentsResolver = self::$container->get('argument_resolver');

        $arguments = $argumentsResolver->getArguments($request, $controller);

        self::assertSame($expectedArguments, $arguments);
    }

    public function provideIntegrationCases(): iterable
    {
        $resource = new ResourceImplementation();

        yield 'simple' => [
            $this->createRequest('PUT', [
                '_api_resource_class' => ResourceImplementation::class,
                '_api_receive' => true,
                '_api_item_operation_name' => 'update',
                'data' => $resource,
            ]),
            static function (ResourceImplementation $payload) {},
            [$resource],
        ];

        yield 'with another argument named $data' => [
            $this->createRequest('PUT', [
                '_api_resource_class' => ResourceImplementation::class,
                '_api_receive' => true,
                '_api_item_operation_name' => 'update',
                'data' => $resource,
            ]),
            static function (ResourceImplementation $payload, $data) {},
            [$resource, $resource],
        ];
    }

    private function createArgumentResolver(): PayloadArgumentResolver
    {
        $metadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactory
            ->create(ResourceImplementation::class)
            ->willReturn(new ResourceMetadata(
                ResourceImplementation::class,
                null,
                null,
                [
                    'update' => [
                        'method' => 'PUT',
                    ],
                    'update_no_deserialize' => [
                        'method' => 'PUT',
                        'deserialize' => false,
                    ],
                    'update_with_dto' => [
                        'method' => 'PUT',
                        'input' => NotResource::class,
                    ],
                ],
                [
                    'create' => [
                        'method' => 'POST',
                    ],
                ]
            ));

        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder
            ->createFromRequest(
                Argument::type(Request::class),
                false,
                Argument::type('array')
            )
            ->will(function (array $arguments) {
                /** @var Request $request */
                $request = $arguments[0];

                $context = [
                    'resource_class' => ResourceImplementation::class,
                ];

                if ('update_with_dto' === $request->attributes->get('_api_item_operation_name')) {
                    $context['input'] = NotResource::class;
                } else {
                    $context['input'] = null;
                }

                return $context;
            });

        return new PayloadArgumentResolver(
            $metadataFactory->reveal(),
            $serializerContextBuilder->reveal()
        );
    }

    private function createRequest(string $method, array $attributes): Request
    {
        $request = Request::create('/foo', $method);
        $request->attributes->replace($attributes);

        return $request;
    }

    private function createArgumentMetadata(?string $type = null, bool $isVariadic = false): ArgumentMetadata
    {
        return new ArgumentMetadata('foo', $type, $isVariadic, false, null);
    }
}
