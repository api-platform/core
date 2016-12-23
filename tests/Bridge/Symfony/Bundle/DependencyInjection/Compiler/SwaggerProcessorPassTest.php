<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\SwaggerProcessorPass;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Swagger\Extractor\SwaggerOperationExtractorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SwaggerProcessorPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new SwaggerProcessorPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $dataProviderPass);

        $swaggerExtractorProcessorProphecy = $this->prophesize(Definition::class);
        $swaggerExtractorProcessorProphecy->addArgument(Argument::type('array'))->shouldBeCalled();

        $SwaggerOperationExtractorProphecy = $this->prophesize(Definition::class);
        $SwaggerOperationExtractorProphecy->getClass()->willReturn($this->prophesize(SwaggerOperationExtractorInterface::class))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasDefinition('api_platform.swagger.processor.swagger_extractor_processor')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.swagger_extractor')->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.swagger.processor.swagger_extractor_processor')->willReturn($swaggerExtractorProcessorProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('foo')->willReturn($SwaggerOperationExtractorProphecy->reveal());
        $containerBuilderProphecy->getDefinition('bar')->willReturn($SwaggerOperationExtractorProphecy->reveal());

        $dataProviderPass->process($containerBuilderProphecy->reveal());
    }

    public function testInvalidArgumentException()
    {
        $dataProviderPass = new SwaggerProcessorPass();

        $swaggerExtractorProcessorProphecy = $this->prophesize(Definition::class);
        $swaggerExtractorProcessorProphecy->addArgument(Argument::type('array'))->shouldNotBeCalled();

        $SwaggerOperationExtractorProphecy = $this->prophesize(Definition::class);
        $SwaggerOperationExtractorProphecy->getClass()->willReturn(Dummy::class)->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasDefinition('api_platform.swagger.processor.swagger_extractor_processor')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.swagger.processor.swagger_extractor_processor')->willReturn($swaggerExtractorProcessorProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.swagger_extractor')->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('foo')->willReturn($SwaggerOperationExtractorProphecy->reveal());

        $this->expectException(InvalidArgumentException::class);

        $dataProviderPass->process($containerBuilderProphecy->reveal());
    }
}
