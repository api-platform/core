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

use ApiPlatform\Hal\Tests\Fixtures\Dummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Processor\LinkedDataPlatformProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkedDataPlatformProcessorTest extends TestCase
{
    private ResourceMetadataCollectionFactoryInterface&MockObject $resourceMetadataCollectionFactory;

    private ResourceClassResolverInterface&MockObject $resourceClassResolver;

    private ProcessorInterface&MockObject $decorated;

    protected function setUp(): void
    {
        $this->resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $this->resourceClassResolver
            ->method('isResourceClass')
            ->willReturn(true);

        $this->resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $this->resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn(
                new ResourceMetadataCollection(Dummy::class, [
                    new ApiResource(operations: [
                        new Get(uriTemplate: '/dummy/{dummyResourceId}{._format}', class: Dummy::class, name: 'get'),
                        new GetCollection(uriTemplate: '/dummy{._format}', class: Dummy::class, name: 'get_collections'),
                        new Post(uriTemplate: '/dummy{._format}', outputFormats: ['jsonld' => ['application/ld+json'], 'text/turtle' => ['text/turtle']], class: Dummy::class, name: 'post'),
                        new Delete(uriTemplate: '/dummy/{dummyResourceId}{._format}', class: Dummy::class, name: 'delete'),
                        new Put(uriTemplate: '/dummy/{dummyResourceId}{._format}', class: Dummy::class, name: 'put'),
                    ]),
                ])
            );

        $this->decorated = $this->createMock(ProcessorInterface::class);
        $this->decorated->method('process')->willReturn(new Response());
    }

    public function testHeadersAcceptPostIsReturnWhenPostAllowed(): void
    {
        $operation = new Get('/dummy{._format}', class: Dummy::class);

        $context = $this->getContext();

        $processor = new LinkedDataPlatformProcessor(
            $this->decorated,
            $this->resourceClassResolver,
            $this->resourceMetadataCollectionFactory
        );
        /** @var Response $response */
        $response = $processor->process(null, $operation, [], $context);

        $this->assertSame('application/ld+json, text/turtle', $response->headers->get('Accept-Post'));
    }

    public function testHeadersAcceptPostIsNotSetWhenPostIsNotAllowed(): void
    {
        $operation = new Get('/dummy/{dummyResourceId}{._format}', class: Dummy::class);
        $context = $this->getContext();

        $processor = new LinkedDataPlatformProcessor(
            $this->decorated,
            $this->resourceClassResolver,
            $this->resourceMetadataCollectionFactory
        );
        /** @var Response $response */
        $response = $processor->process(null, $operation, [], $context);

        $this->assertNull($response->headers->get('Accept-Post'));
    }

    public function testHeaderAllowReflectsResourceAllowedMethods(): void
    {
        $operation = new Get('/dummy{._format}', class: Dummy::class);
        $context = $this->getContext();

        $processor = new LinkedDataPlatformProcessor(
            $this->decorated,
            $this->resourceClassResolver,
            $this->resourceMetadataCollectionFactory
        );
        /** @var Response $response */
        $response = $processor->process(null, $operation, [], $context);
        $allowHeader = $response->headers->get('Allow');
        $this->assertStringContainsString('OPTIONS', $allowHeader);
        $this->assertStringContainsString('HEAD', $allowHeader);
        $this->assertStringContainsString('GET', $allowHeader);
        $this->assertStringContainsString('POST', $allowHeader);
        $operation = new Get('/dummy/{dummyResourceId}{._format}', class: Dummy::class);

        /** @var Response $response */
        $processor = new LinkedDataPlatformProcessor(
            $this->decorated,
            $this->resourceClassResolver,
            $this->resourceMetadataCollectionFactory
        );
        /** @var Response $response */
        $response = $processor->process('data', $operation, [], $this->getContext());
        $allowHeader = $response->headers->get('Allow');
        $this->assertStringContainsString('OPTIONS', $allowHeader);
        $this->assertStringContainsString('HEAD', $allowHeader);
        $this->assertStringContainsString('GET', $allowHeader);
        $this->assertStringContainsString('PUT', $allowHeader);
        $this->assertStringContainsString('DELETE', $allowHeader);
    }

    public function testProcessorWithoutRequiredConditionReturnOriginalResponse(): void
    {
        // Operation is an Error
        $processor = new LinkedDataPlatformProcessor($this->decorated, $this->resourceClassResolver, $this->resourceMetadataCollectionFactory);
        $response = $processor->process(null, new Error(), $this->getContext());
        $this->assertNull($response->headers->get('Allow'));
    }

    private function createGetRequest(): Request
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'application/ld+json');

        return $request;
    }

    private function getContext(): array
    {
        return [
            'resource_class' => Dummy::class,
            'request' => $this->createGetRequest(),
        ];
    }
}
