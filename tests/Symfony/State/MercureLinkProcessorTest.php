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

namespace ApiPlatform\Tests\Symfony\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\State\MercureLinkProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;

class MercureLinkProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $obj = new \stdClass();
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process');
        $discovery = new Discovery(new HubRegistry($this->createMock(HubInterface::class), ['example.com' => $this->createMock(HubInterface::class)]));
        $operation = new Get(mercure: ['hub' => 'example.com']);
        $processor = new MercureLinkProcessor($decorated, $discovery);
        $processor->process($obj, $operation, [], ['request' => $request]);
    }
}
