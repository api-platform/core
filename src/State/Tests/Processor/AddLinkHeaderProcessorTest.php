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

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class AddLinkHeaderProcessorTest extends TestCase
{
    public function testWithoutLinks(): void
    {
        $data = new \stdClass();
        $operation = new Get();
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($data);
        $processor = new AddLinkHeaderProcessor($decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }
}
