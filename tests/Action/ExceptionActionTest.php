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

namespace ApiPlatform\Tests\Action;

use ApiPlatform\Action\ExceptionAction;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\ProphecyTrait;
use DomainException;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Debug\Exception\FlattenException as LegacyFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @group time-sensitive
 */
class ExceptionActionTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public function testActionWithCatchableException()
    {
        $serializerException = $this->prophesize(ExceptionInterface::class);
        if (!is_a(ExceptionInterface::class, \Throwable::class, true)) {
            $serializerException->willExtend(\Exception::class);
        }
        $flattenException = class_exists(FlattenException::class) ? FlattenException::create($serializerException->reveal()) : LegacyFlattenException::create($serializerException->reveal()); /** @phpstan-ignore-line */
        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($flattenException, 'jsonproblem', ['statusCode' => Response::HTTP_BAD_REQUEST])->willReturn('');

        $exceptionAction = new ExceptionAction($serializer->reveal(), ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']], [ExceptionInterface::class => Response::HTTP_BAD_REQUEST, InvalidArgumentException::class => Response::HTTP_BAD_REQUEST]);

        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');

        $response = $exceptionAction($flattenException, $request);
        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/problem+json; charset=utf-8'));
        $this->assertTrue($response->headers->contains('X-Content-Type-Options', 'nosniff'));
        $this->assertTrue($response->headers->contains('X-Frame-Options', 'deny'));
    }

    /**
     * @dataProvider provideOperationExceptionToStatusCases
     */
    public function testActionWithOperationExceptionToStatus(
        array $globalExceptionToStatus,
        ?array $resourceExceptionToStatus,
        ?array $operationExceptionToStatus,
        int $expectedStatusCode
    ) {
        $exception = new DomainException();
        $flattenException = FlattenException::create($exception);

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($flattenException, 'jsonproblem', ['statusCode' => $expectedStatusCode])->willReturn('');

        /** @var HttpOperation $operation */
        $operation = (new Get())->withShortName('Foo');
        $resource = (new ApiResource())->withShortName('Foo');
        if ($resourceExceptionToStatus) {
            $resource = $resource->withExceptionToStatus($resourceExceptionToStatus);
            $operation = $operation->withExceptionToStatus($resourceExceptionToStatus);
        }

        if ($operationExceptionToStatus) {
            $operation = $operation->withExceptionToStatus($operationExceptionToStatus);
        }

        $resource = $resource->withOperations(new Operations(['operation' => $operation]));

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            $resource,
        ]));

        $exceptionAction = new ExceptionAction(
            $serializer->reveal(),
            [
                'jsonproblem' => ['application/problem+json'],
                'jsonld' => ['application/ld+json'],
            ],
            $globalExceptionToStatus,
            $resourceMetadataFactory->reveal()
        );

        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');
        $request->attributes->replace([
            '_api_resource_class' => 'Foo',
            '_api_operation_name' => 'operation',
        ]);

        $response = $exceptionAction($flattenException, $request);

        $this->assertSame('', $response->getContent());
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/problem+json; charset=utf-8'));
        $this->assertTrue($response->headers->contains('X-Content-Type-Options', 'nosniff'));
        $this->assertTrue($response->headers->contains('X-Frame-Options', 'deny'));
    }

    public function provideOperationExceptionToStatusCases()
    {
        yield 'no mapping' => [
            [],
            null,
            null,
            500,
        ];

        yield 'on global attributes' => [
            [DomainException::class => 100],
            null,
            null,
            100,
        ];

        yield 'on global attributes with empty resource and operation attributes' => [
            [DomainException::class => 100],
            [],
            [],
            100,
        ];

        yield 'on global attributes and resource attributes' => [
            [DomainException::class => 100],
            [DomainException::class => 200],
            null,
            200,
        ];

        yield 'on global attributes and resource attributes with empty operation attributes' => [
            [DomainException::class => 100],
            [DomainException::class => 200],
            [],
            200,
        ];

        yield 'on global attributes and operation attributes' => [
            [DomainException::class => 100],
            null,
            [DomainException::class => 300],
            300,
        ];

        yield 'on global attributes and operation attributes with empty resource attributes' => [
            [DomainException::class => 100],
            [],
            [DomainException::class => 300],
            300,
        ];

        yield 'on global, resource and operation attributes' => [
            [DomainException::class => 100],
            [DomainException::class => 200],
            [DomainException::class => 300],
            300,
        ];

        yield 'on resource attributes' => [
            [],
            [DomainException::class => 200],
            null,
            200,
        ];

        yield 'on resource attributes with empty operation attributes' => [
            [],
            [DomainException::class => 200],
            [],
            200,
        ];

        yield 'on resource and operation attributes' => [
            [],
            [DomainException::class => 200],
            [DomainException::class => 300],
            300,
        ];

        yield 'on operation attributes' => [
            [],
            null,
            [DomainException::class => 300],
            300,
        ];

        yield 'on operation attributes with empty resource attributes' => [
            [],
            [],
            [DomainException::class => 300],
            300,
        ];
    }

    public function testActionWithUncatchableException()
    {
        $serializerException = $this->prophesize(ExceptionInterface::class);
        if (!is_a(ExceptionInterface::class, \Throwable::class, true)) {
            $serializerException->willExtend(\Exception::class);
        }

        $flattenException = class_exists(FlattenException::class) ? FlattenException::create($serializerException->reveal()) : LegacyFlattenException::create($serializerException->reveal()); /** @phpstan-ignore-line */
        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($flattenException, 'jsonproblem', ['statusCode' => $flattenException->getStatusCode()])->willReturn('');

        $exceptionAction = new ExceptionAction($serializer->reveal(), ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']]);

        $request = new Request();
        $request->setFormat('jsonproblem', 'application/problem+json');

        $expected = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]);

        $this->assertEquals($expected, $exceptionAction($flattenException, $request));
    }
}
