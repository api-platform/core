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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ElasticsearchClientPassTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(CompilerPassInterface::class, new ElasticsearchClientPass());
    }

    public function testProcess()
    {
        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setFactory([ClientBuilder::class, 'fromConfig'])->shouldBeCalled();
        $clientDefinitionProphecy->setArguments(
            Argument::allOf(
                Argument::withEntry(0, Argument::allOf(
                    Argument::withEntry('hosts', ['http://localhost:9200']),
                    Argument::withEntry('logger', Argument::type(Reference::class)),
                    Argument::withEntry('tracer', Argument::type(Reference::class)),
                    Argument::size(3)
                )),
                Argument::size(1)
            )
        )->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.hosts')->willReturn(['http://localhost:9200'])->shouldBeCalled();
        $containerBuilderProphecy->has('logger')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.elasticsearch.client')->willReturn($clientDefinitionProphecy)->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutConfiguration()
    {
        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setFactory([ClientBuilder::class, 'build'])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.hosts')->willReturn([])->shouldBeCalled();
        $containerBuilderProphecy->has('logger')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.elasticsearch.client')->willReturn($clientDefinitionProphecy)->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithElasticsearchDisabled()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.elasticsearch.enabled')->willReturn(false)->shouldBeCalled();

        (new ElasticsearchClientPass())->process($containerBuilderProphecy->reveal());
    }
}
