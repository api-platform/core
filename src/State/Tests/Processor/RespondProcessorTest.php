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

namespace ApiPlatform\State\Tests\Processor;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Processor\RespondProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RespondProcessorTest extends TestCase
{
    private ResourceMetadataCollectionFactoryInterface&MockObject $resourceMetadataCollectionFactory;
    private RespondProcessor $processor;

    protected function setUp(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver
            ->method('isResourceClass')
            ->willReturn(true);

        $this->resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        $this->processor = new RespondProcessor(
            null,
            $resourceClassResolver,
            null,
            $this->resourceMetadataCollectionFactory
        );
    }

    public function testHeadersAcceptPostIsSetCorrectly(): void
    {
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection('DummyResourceClass'));

        $operation = new HttpOperation('GET');
        $context = [
            'resource_class' => 'SomeResourceClass',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $this->assertSame('text/turtle, application/ld+json', $response->headers->get('Accept-Post'));
    }

    public function testHeaderAllowHasHeadOptionsByDefault(): void
    {
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection('DummyResourceClass'));

        $operation = new HttpOperation('GET');
        $context = [
            'resource_class' => 'SomeResourceClass',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $this->assertSame('OPTIONS, HEAD', $response->headers->get('Allow'));
    }

    public function testHeaderAllowReflectsResourceAllowedMethods(): void
    {
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(
                new ResourceMetadataCollection('DummyResource', [
                    new ApiResource(operations: [
                        'get' => new Get(name: 'get'),
                        'post' => new Post(name: 'post'),
                        'delete' => new Delete(name: 'delete'),
                    ]),
                ])
            );

        $operation = new HttpOperation('GET');
        $context = [
            'resource_class' => 'SomeResourceClass',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $allowHeader = $response->headers->get('Allow');
        $this->assertStringContainsString('OPTIONS', $allowHeader);
        $this->assertStringContainsString('HEAD', $allowHeader);
        $this->assertStringContainsString('GET', $allowHeader);
        $this->assertStringContainsString('POST', $allowHeader);
        $this->assertStringContainsString('DELETE', $allowHeader);
        $this->assertStringNotContainsString('PATCH', $allowHeader);
        $this->assertStringNotContainsString('PUT', $allowHeader);
    }

    public function testHeaderAllowReflectsAllowedResourcesGetPutPatch(): void
    {
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(
                new ResourceMetadataCollection('DummyResource', [
                    new ApiResource(operations: [
                        'get' => new Get(name: 'get'),
                        'patch' => new Patch(name: 'patch'),
                        'put' => new Put(name: 'put'),
                    ]),
                ])
            );

        $operation = new HttpOperation('GET');
        $context = [
            'resource_class' => 'SomeResourceClass',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $allowHeader = $response->headers->get('Allow');
        $this->assertStringContainsString('OPTIONS', $allowHeader);
        $this->assertStringContainsString('HEAD', $allowHeader);
        $this->assertStringContainsString('GET', $allowHeader);
        $this->assertStringContainsString('PATCH', $allowHeader);
        $this->assertStringContainsString('PUT', $allowHeader);
        $this->assertStringNotContainsString('POST', $allowHeader);
        $this->assertStringNotContainsString('DELETE', $allowHeader);
    }

    private function createGetRequest(): Request
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'application/ld+json');

        return $request;
    }
}
