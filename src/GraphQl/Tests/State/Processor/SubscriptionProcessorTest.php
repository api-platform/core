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

namespace ApiPlatform\GraphQl\Tests\State\Processor;

use ApiPlatform\GraphQl\State\Processor\SubscriptionProcessor;
use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class SubscriptionProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $operation = new Subscription(mercure: ['hub' => 'mercure.rocks']);
        $context = [];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->willReturn([]);
        $subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $subscriptionManager->expects($this->once())->method('retrieveSubscriptionId')->willReturn('/1');
        $mercureSubscriptionIriGenerator = $this->createMock(MercureSubscriptionIriGeneratorInterface::class);
        $mercureSubscriptionIriGenerator->expects($this->once())->method('generateMercureUrl')->with('/1', $operation->getMercure()['hub']);
        $processor = new SubscriptionProcessor($decorated, $subscriptionManager, $mercureSubscriptionIriGenerator);
        $processor->process([], $operation, [], $context);
    }

    public function testProcessWithoutId(): void
    {
        $operation = new Subscription(mercure: ['hub' => 'mercure.rocks']);
        $context = [];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->willReturn([]);
        $subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $subscriptionManager->expects($this->once())->method('retrieveSubscriptionId')->willReturn(null);
        $mercureSubscriptionIriGenerator = $this->createMock(MercureSubscriptionIriGeneratorInterface::class);
        $mercureSubscriptionIriGenerator->expects($this->never())->method('generateMercureUrl')->with('/1', $operation->getMercure()['hub']);
        $processor = new SubscriptionProcessor($decorated, $subscriptionManager, $mercureSubscriptionIriGenerator);
        $processor->process([], $operation, [], $context);
    }

    public function testProcessWithoutMercure(): void
    {
        $operation = new Subscription();
        $context = [];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->willReturn([]);
        $subscriptionManager = $this->createMock(SubscriptionManagerInterface::class);
        $subscriptionManager->expects($this->never())->method('retrieveSubscriptionId')->willReturn(null);
        $mercureSubscriptionIriGenerator = $this->createMock(MercureSubscriptionIriGeneratorInterface::class);
        $mercureSubscriptionIriGenerator->expects($this->never())->method('generateMercureUrl');
        $processor = new SubscriptionProcessor($decorated, $subscriptionManager, $mercureSubscriptionIriGenerator);
        $processor->process([], $operation, [], $context);
    }
}
