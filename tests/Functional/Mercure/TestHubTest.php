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

namespace ApiPlatform\Tests\Functional\Mercure;

use ApiPlatform\Tests\Fixtures\TestBundle\Mercure\TestHub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class TestHubTest extends TestCase
{
    public function testItRecordsWithoutReachingTheHub(): void
    {
        $inner = $this->createMock(HubInterface::class);
        $inner->expects($this->never())->method('publish');

        $hub = new TestHub($inner);
        $update = new Update('/dummies/1', '{}');

        $id = $hub->publish($update);

        $this->assertNotSame('', $id);
        $this->assertSame([$update], $hub->getUpdates());
    }

    public function testItForwardsWhenPublishingIsEnabled(): void
    {
        $update = new Update('/dummies/1', '{}');

        $inner = $this->createMock(HubInterface::class);
        $inner->expects($this->once())->method('publish')->with($update)->willReturn('urn:uuid:from-hub');

        $hub = new TestHub($inner, true);

        $this->assertSame('urn:uuid:from-hub', $hub->publish($update));
        $this->assertSame([$update], $hub->getUpdates());
    }
}
