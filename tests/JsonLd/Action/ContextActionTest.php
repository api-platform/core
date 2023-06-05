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

namespace ApiPlatform\Tests\JsonLd\Action;

use ApiPlatform\JsonLd\Action\ContextAction;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ContextActionTest extends TestCase
{
    use ProphecyTrait;

    public function testContextActionWithEntrypoint(): void
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $contextBuilderProphecy->getEntrypointContext()->willReturn(['/entrypoints']);
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());

        $this->assertEquals(['@context' => ['/entrypoints']], $contextAction('Entrypoint'));
    }

    public function testContextActionWithContexts(): void
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $contextBuilderProphecy->getBaseContext()->willReturn(['/contexts']);
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());

        $this->assertEquals(['@context' => ['/contexts']], $contextAction('ConstraintViolationList'));
    }

    public function testContextActionWithResourceClass(): void
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummy']));
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());
        $contextBuilderProphecy->getResourceContext('dummy')->willReturn(['/dummies']);

        $resourceMetadataCollectionFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(
            new ResourceMetadataCollection('dummy', [
                (new ApiResource())
                ->withShortName('dummy')
                ->withDescription('dummy')
                ->withTypes(['#dummy'])
                ->withOperations(new Operations([
                    'get' => (new Get())->withShortName('dummy'),
                    'put' => (new Put())->withShortName('dummy'),
                    'get_collection' => (new GetCollection())->withShortName('dummy'),
                    'post' => (new Post())->withShortName('dummy'),
                    'custom' => (new Get())->withUriTemplate('/foo')->withShortName('dummy'),
                    'custom2' => (new Post())->withUriTemplate('/foo')->withShortName('dummy'),
                ])),
            ])
        );
        $this->assertEquals(['@context' => ['/dummies']], $contextAction('dummy'));
    }

    public function testContextActionWithThrown(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['gerard']));
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal());

        $resourceMetadataCollectionFactoryProphecy->create('gerard')->shouldBeCalled()->willReturn(
            new ResourceMetadataCollection('gerard', [
                (new ApiResource())
                    ->withShortName('gerard')
                    ->withDescription('gerard')
                    ->withTypes(['#dummy'])
                    ->withOperations(new Operations([
                        'get' => (new Get())->withShortName('gerard'),
                        'put' => (new Put())->withShortName('gerard'),
                        'get_collection' => (new GetCollection())->withShortName('gerard'),
                        'post' => (new Post())->withShortName('gerard'),
                        'custom' => (new Get())->withUriTemplate('/foo')->withShortName('gerard'),
                        'custom2' => (new Post())->withUriTemplate('/foo')->withShortName('gerard'),
                    ])),
            ])
        );
        $contextAction('dummy');
    }
}
