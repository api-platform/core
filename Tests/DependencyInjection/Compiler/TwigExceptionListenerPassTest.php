<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\DependencyInjection\Compiler;

use Dunglas\ApiBundle\DependencyInjection\Compiler\TwigExceptionListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class TwigExceptionListenerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->register('twig.exception_listener');
        $containerBuilder->register('api.hydra.listener.exception');

        (new TwigExceptionListenerPass())->process($containerBuilder);

        $this->assertFalse($containerBuilder->hasDefinition('twig.exception_listener'));
        $this->assertTrue($containerBuilder->hasDefinition('api.hydra.listener.exception'));
    }
}
