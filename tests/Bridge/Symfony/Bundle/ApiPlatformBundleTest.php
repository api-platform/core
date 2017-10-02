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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle;

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DoctrineQueryExtensionPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $containerProphecy = $this->prophesize(ContainerBuilder::class);
        $containerProphecy->addCompilerPass(Argument::type(DataProviderPass::class))->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(FilterPass::class))->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(DoctrineQueryExtensionPass::class))->shouldBeCalled();

        $bundle = new ApiPlatformBundle();
        $bundle->build($containerProphecy->reveal());
    }
}
