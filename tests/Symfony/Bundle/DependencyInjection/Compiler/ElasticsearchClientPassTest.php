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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ElasticsearchClientPassTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(CompilerPassInterface::class, new ElasticsearchClientPass());
    }

    public function testProcess(): void
    {
        if (class_exists(\Elasticsearch\ClientBuilder::class)) {
            $clientBuilder = \Elasticsearch\ClientBuilder::class;

            $expectedArguments = [
                Argument::withEntry('hosts', ['http://localhost:9200']),
                Argument::withEntry('logger', Argument::type(Reference::class)),
                Argument::withEntry('tracer', Argument::type(Reference::class)),
                Argument::size(3),
            ];
        } else {
            $clientBuilder = \Elastic\Elasticsearch\ClientBuilder::class;

            $expectedArguments = [
                Argument::withEntry('hosts', ['http://localhost:9200']),
                Argument::withEntry('logger', Argument::type(Reference::class)),
                Argument::size(2),
            ];
        }

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        // @noRector \Rector\Php81\Rector\Array_\FirstClassCallableRector
        $clientDefinitionProphecy->setFactory([$clientBuilder, 'fromConfig'])->willReturn($clientDefinitionProphecy->reveal())->shouldBeCalled();
        $clientDefinitionProphecy->setArguments(
            Argument::allOf(
                Argument::withEntry(0, Argument::allOf(...$expectedArguments)),
                Argument::size(1)
            )
        )->willReturn($clientDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.hosts')->willReturn(['http://localhost:9200'])->shouldBeCalled();
        $containerBuilderProphecy->has('logger')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.elasticsearch.client')->willReturn($clientDefinitionProphecy->reveal())->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutConfiguration(): void
    {
        $clientBuilder = class_exists(\Elasticsearch\ClientBuilder::class) ? \Elasticsearch\ClientBuilder::class : \Elastic\Elasticsearch\ClientBuilder::class;

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        // @noRector \Rector\Php81\Rector\Array_\FirstClassCallableRector
        $clientDefinitionProphecy->setFactory([$clientBuilder, 'build'])->willReturn($clientDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.hosts')->willReturn([])->shouldBeCalled();
        $containerBuilderProphecy->has('logger')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.elasticsearch.client')->willReturn($clientDefinitionProphecy->reveal())->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithElasticsearchDisabled(): void
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(false)->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }
}
