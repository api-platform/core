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

namespace ApiPlatform\HttpCache\Tests\State;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\HttpCache\State\PurgeTagsProcessor;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class PurgeTagsProcessorTest extends TestCase
{
    public function testDeleteCollectsTagsBeforeTheDecoratedProcessor(): void
    {
        $resource = new \stdClass();
        $calls = [];

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())->method('process')->willReturnCallback(static function () use (&$calls) {
            $calls[] = 'process';

            return null;
        });

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())->method('getTags')->with($resource, $this->isInstanceOf(Delete::class))->willReturnCallback(static function () use (&$calls) {
            $calls[] = 'getTags';

            return ['/parents/1/children'];
        });

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children']);

        (new PurgeTagsProcessor($decorated, $purger, [$provider]))->process($resource, new Delete(), [], []);

        $this->assertSame(['getTags', 'process'], $calls);
    }

    public function testNonDeleteCollectsTagsFromTheDecoratedResult(): void
    {
        $data = new \stdClass();
        $result = new \stdClass();
        $previousData = new \stdClass();
        $context = ['previous_data' => $previousData];

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($result);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())->method('getTags')->with($result, $this->isInstanceOf(Put::class), $context)->willReturn(['/parents/1/children', '/parents/2/children']);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children', '/parents/2/children']);

        $this->assertSame($result, (new PurgeTagsProcessor($decorated, $purger, [$provider]))->process($data, new Put(), [], $context));
    }

    public function testTagsAreDeduplicatedAcrossProviders(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $first = $this->createStub(PurgeTagProviderInterface::class);
        $first->method('getTags')->willReturn(['/a', '/b']);
        $second = $this->createStub(PurgeTagProviderInterface::class);
        $second->method('getTags')->willReturn(['/b', '/c']);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/a', '/b', '/c']);

        (new PurgeTagsProcessor($decorated, $purger, [$first, $second]))->process($resource, new Put(), [], []);
    }

    public function testNoPurgeWithoutTags(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider = $this->createStub(PurgeTagProviderInterface::class);
        $provider->method('getTags')->willReturn([]);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        (new PurgeTagsProcessor($decorated, $purger, [$provider]))->process($resource, new Put(), [], []);
    }

    public function testNonObjectResultDoesNotCallProviders(): void
    {
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn(null);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->never())->method('getTags');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        (new PurgeTagsProcessor($decorated, $purger, [$provider]))->process(new \stdClass(), new Put(), [], []);
    }
}
