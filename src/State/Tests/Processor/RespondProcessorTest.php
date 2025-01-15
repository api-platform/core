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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
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
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(
                new ResourceMetadataCollection('DummyResource', [
                    new ApiResource(operations: [
                        new Get(uriTemplate: '/dummy_resources/{dummyResourceId}{._format}', name: 'get'),
                        new GetCollection(uriTemplate: '/dummy_resources{._format}', name: 'get_collections'),
                        new Post(uriTemplate: '/dummy_resources{._format}', name: 'post'),
                        new Delete(uriTemplate: '/dummy_resources/{dummyResourceId}{._format}', name: 'delete'),
                        new Put(uriTemplate: '/dummy_resources/{dummyResourceId}{._format}', name: 'put'),
                    ]),
                ])
            );

        $this->processor = new RespondProcessor(
            null,
            $resourceClassResolver,
            null,
            $this->resourceMetadataCollectionFactory
        );
    }

    public function testHeadersAcceptPostIsReturnWhenPostAllowed(): void
    {
        $operation = (new HttpOperation('GET', '/dummy_resources{._format}', outputFormats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]));
        $context = [
            'resource_class' => 'DummyResource',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);
        $this->assertSame('application/ld+json, application/json', $response->headers->get('Accept-Post'));
    }

    public function testHeadersAcceptPostIsNotSetWhenPostIsNotAllowed(): void
    {
        $operation = (new HttpOperation('GET', '/dummy_resources/{dummyResourceId}{._format}'));
        $context = [
            'resource_class' => 'DummyResource',
            'request' => $this->createGetRequest(),
        ];

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $this->assertNull($response->headers->get('Accept-Post'));
    }

    public function testHeaderAllowReflectsResourceAllowedMethods(): void
    {
        $operation = (new HttpOperation('GET', '/dummy_resources{._format}'));
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
        $this->assertStringNotContainsString('DELETE', $allowHeader);

        $context = [
            'resource_class' => 'SomeResourceClass',
            'request' => $this->createGetRequest(),
        ];
        $operation = (new HttpOperation('GET', '/dummy_resources/{dummyResourceId}{._format}'));

        /** @var Response $response */
        $response = $this->processor->process(null, $operation, [], $context);

        $allowHeader = $response->headers->get('Allow');
        $this->assertStringContainsString('OPTIONS', $allowHeader);
        $this->assertStringContainsString('HEAD', $allowHeader);
        $this->assertStringContainsString('GET', $allowHeader);
        $this->assertStringContainsString('PUT', $allowHeader);
        $this->assertStringContainsString('DELETE', $allowHeader);
        $this->assertStringNotContainsString('POST', $allowHeader);
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
