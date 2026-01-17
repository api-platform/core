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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RespondProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testRedirectToOperation(): void
    {
        $canonicalUriTemplateRedirectingOperation = new Get(
            status: 302,
            class: Employee::class,
            extraProperties: [
                'canonical_uri_template' => '/canonical',
            ]
        );

        $alternateRedirectingResourceOperation = new Get(
            status: 308,
            class: Employee::class,
            extraProperties: [
                'is_alternate_resource_metadata' => true,
            ]
        );

        $alternateResourceOperation = new Get(
            class: Employee::class,
            extraProperties: [
                'is_alternate_resource_metadata' => true,
            ]
        );

        $operationMetadataFactory = $this->prophesize(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory
            ->create('/canonical', Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn(new Get(uriTemplate: '/canonical'));

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver
            ->isResourceClass(Employee::class)
            ->willReturn(true);

        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $iriConverter
            ->getIriFromResource(Argument::cetera())
            ->will(static function (array $args): string {
                return ($args[2] ?? null)?->getUriTemplate() ?? '/default';
            });

        $respondProcessor = new RespondProcessor($iriConverter->reveal(), $resourceClassResolver->reveal(), $operationMetadataFactory->reveal());

        $response = $respondProcessor->process('content', $canonicalUriTemplateRedirectingOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/canonical', $response->headers->get('Location'));

        $response = $respondProcessor->process('content', $alternateRedirectingResourceOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/default', $response->headers->get('Location'));

        $response = $respondProcessor->process('content', $alternateResourceOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('Location'));
    }

    public function testAddsExceptionHeaders(): void
    {
        $operation = new Get();

        $respondProcessor = new RespondProcessor();
        $req = new Request();
        $req->attributes->set('exception', new TooManyRequestsHttpException(32));
        $response = $respondProcessor->process('content', new Get(), context: [
            'request' => $req,
        ]);

        $this->assertSame('32', $response->headers->get('retry-after'));
    }

    public function testAddsHeaders(): void
    {
        $operation = new Get(headers: ['foo' => 'bar']);

        $respondProcessor = new RespondProcessor();
        $req = new Request();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => $req,
        ]);

        $this->assertSame('bar', $response->headers->get('foo'));
    }

    public function testAddsLinkedDataPlatformHeaders(): void
    {
        $getOperation = new Get(uriTemplate: '/employees/{id}', class: Employee::class);
        $postOperation = new Post(uriTemplate: '/employees/{id}', class: Employee::class, outputFormats: ['jsonld' => ['application/ld+json']]);

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Employee::class)->willReturn(true);

        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Employee::class)->willReturn(new ResourceMetadataCollection(Employee::class, [
            new ApiResource(operations: [
                'get' => $getOperation,
                'post' => $postOperation,
            ]),
        ]));

        $respondProcessor = new RespondProcessor(
            null,
            $resourceClassResolver->reveal(),
            null,
            $resourceMetadataCollectionFactory->reveal()
        );

        $req = new Request();
        $response = $respondProcessor->process('content', $getOperation, context: [
            'request' => $req,
        ]);

        $this->assertSame('OPTIONS, HEAD, GET, POST', $response->headers->get('Allow'));
        $this->assertSame('application/ld+json', $response->headers->get('Accept-Post'));
    }

    public function testDoesNotAddLinkedDataPlatformHeadersWithoutFactory(): void
    {
        $operation = new Get(uriTemplate: '/employees/{id}', class: Employee::class);

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Employee::class)->willReturn(true);

        $respondProcessor = new RespondProcessor(
            null,
            $resourceClassResolver->reveal(),
            null,
            null // No ResourceMetadataCollectionFactory
        );

        $req = new Request();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => $req,
        ]);

        $this->assertNull($response->headers->get('Allow'));
        $this->assertNull($response->headers->get('Accept-Post'));
    }
}
