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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataPersisterPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class DataPersisterPassTest extends TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf(CompilerPassInterface::class, new DataPersisterPass());
    }

    public function testProcess()
    {
        $dataPersisterDefinitionProphecy = $this->prophesize(Definition::class);
        $dataPersisterDefinitionProphecy->addArgument([new Reference('foo'), new Reference('bar')])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.data_persister', true)->willReturn(['foo' => [], 'bar' => []])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.data_persister')->willReturn($dataPersisterDefinitionProphecy->reveal())->shouldBeCalled();

        (new DataPersisterPass())->process($containerBuilderProphecy->reveal());
    }
}
