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
use ApiPlatform\State\ChainProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class ChainProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testChainProcessor()
    {
        $firstProcessor = $this->prophesize(ProcessorInterface::class);
        $firstProcessor->resumable('operationName', [])->willReturn(true)->shouldBeCalled();
        $firstProcessor->supports('data', [], 'operationName', [])->willReturn(true)->shouldBeCalled();
        $firstProcessor->process('data', [], 'operationName', [])->willReturn('value')->shouldBeCalled();

        $secondProcessor = $this->prophesize(ProcessorInterface::class);
        $secondProcessor->resumable('operationName', [])->willReturn(false)->shouldBeCalled();
        $secondProcessor->supports('data', [], 'operationName', [])->willReturn(false)->shouldBeCalled();

        $thirdProcessor = $this->prophesize(ProcessorInterface::class);
        $thirdProcessor->resumable('operationName', [])->willReturn(false)->shouldBeCalled();
        $thirdProcessor->supports('data', [], 'operationName', [])->willReturn(false)->shouldBeCalled();
        $thirdProcessor->process('data', [], 'operationName', [])->shouldNotBeCalled();

        $firstChainProcessor = new ChainProcessor([
            $firstProcessor->reveal(),
            $secondProcessor->reveal(),
        ]);

        $secondChainProcessor = new ChainProcessor([
            $secondProcessor->reveal(),
            $thirdProcessor->reveal(),
        ]);

        $thirdChainProcessor = new ChainProcessor([
            $thirdProcessor->reveal(),
            $firstProcessor->reveal(),
        ]);

        $this->assertTrue($firstChainProcessor->resumable('operationName', []));
        $this->assertFalse($secondChainProcessor->resumable('operationName', []));

        $this->assertTrue($firstChainProcessor->supports('data', [], 'operationName', []));
        $this->assertFalse($secondChainProcessor->supports('data', [], 'operationName', []));

        $this->assertEquals('value', $firstChainProcessor->process('data', [], 'operationName', []));
        $this->assertEquals('value', $thirdChainProcessor->process('data', [], 'operationName', []));
    }
}
