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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\State\ChainProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;

class ChainProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testChainProvider()
    {
        $a = $this->prophesize(ProviderInterface::class);
        $a->supports('class', [], 'operationName', [])->willReturn(false);
        $a->provide('class', [], 'operationName', [])->shouldNotBeCalled();

        $b = $this->prophesize(ProviderInterface::class);
        $b->supports('class', [], 'operationName', [])->willReturn(true);
        $b->provide('class', [], 'operationName', [])->willReturn('value');

        $chainProvider = new ChainProvider([
            $a->reveal(),
            $b->reveal(),
        ]);

        $this->assertEquals('value', $chainProvider->provide('class', [], 'operationName', []));
        $this->assertTrue($chainProvider->supports('class', [], 'operationName', []));
    }

    /**
     * @requires PHP 8.0
     */
    public function testReturnValueWhenNoProvider()
    {
        $a = $this->prophesize(ProviderInterface::class);
        $a->supports('class', ['id' => 1], 'operationName', [])->willReturn(false);
        $a->supports('class', [], 'operationName', [])->willReturn(false);
        $a->provide('class', ['id' => 1], 'operationName', [])->shouldNotBeCalled();
        $a->provide('class', [], 'operationName', [])->shouldNotBeCalled();

        $chainProvider = new ChainProvider([
            $a->reveal(),
        ]);

        $this->assertNull($chainProvider->provide('class', ['id' => 1], 'operationName', []));
        $this->assertEquals([], $chainProvider->provide('class', [], 'operationName', []));
        $this->assertFalse($chainProvider->supports('class', [], 'operationName', []));
    }
}
