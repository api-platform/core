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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class PurgeTagsProcessorTest extends TestCase
{
    public function testCallsGetTagsForInsertOnPost(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())->method('getTagsForInsert')->with($resource)->willReturn(['/parents/1/children']);
        $provider->expects($this->never())->method('getTagsForUpdate');
        $provider->expects($this->never())->method('getTagsForDelete');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children']);

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider]);
        $processor->process($resource, new Post(), [], []);
    }

    public function testCallsGetTagsForUpdateOnPut(): void
    {
        $resource = new \stdClass();
        $previousResource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->never())->method('getTagsForInsert');
        $provider->expects($this->once())->method('getTagsForUpdate')->with($resource, $previousResource)->willReturn(['/parents/1/children', '/parents/2/children']);
        $provider->expects($this->never())->method('getTagsForDelete');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children', '/parents/2/children']);

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider]);
        $processor->process($resource, new Put(), [], ['previous_data' => $previousResource]);
    }

    public function testCallsGetTagsForUpdateOnPatch(): void
    {
        $resource = new \stdClass();
        $previousResource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())->method('getTagsForUpdate')->with($resource, $previousResource)->willReturn(['/parents/1/children']);
        $provider->expects($this->never())->method('getTagsForInsert');
        $provider->expects($this->never())->method('getTagsForDelete');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children']);

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider]);
        $processor->process($resource, new Patch(), [], ['previous_data' => $previousResource]);
    }

    public function testCallsGetTagsForDeleteOnDelete(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn(null);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->never())->method('getTagsForInsert');
        $provider->expects($this->never())->method('getTagsForUpdate');
        $provider->expects($this->once())->method('getTagsForDelete')->with($resource)->willReturn(['/parents/1/children']);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children']);

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider]);
        $processor->process($resource, new Delete(), [], []);
    }

    public function testNoPurgeWhenNoTags(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider = $this->createStub(PurgeTagProviderInterface::class);
        $provider->method('getTagsForInsert')->willReturn([]);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider]);
        $processor->process($resource, new Post(), [], []);
    }

    public function testDeduplicatesTags(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $provider1 = $this->createStub(PurgeTagProviderInterface::class);
        $provider1->method('getTagsForInsert')->willReturn(['/parents/1/children']);

        $provider2 = $this->createStub(PurgeTagProviderInterface::class);
        $provider2->method('getTagsForInsert')->willReturn(['/parents/1/children', '/parents/2/children']);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/parents/1/children', '/parents/2/children']);

        $processor = new PurgeTagsProcessor($decorated, $purger, [$provider1, $provider2]);
        $processor->process($resource, new Post(), [], []);
    }

    public function testNoPurgeWhenNoProviders(): void
    {
        $resource = new \stdClass();

        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($resource);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        $processor = new PurgeTagsProcessor($decorated, $purger, []);
        $processor->process($resource, new Post(), [], []);
    }
}
