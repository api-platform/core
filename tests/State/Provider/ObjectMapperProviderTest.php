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

namespace ApiPlatform\Tests\State\Provider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Provider\ObjectMapperProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperProviderTest extends TestCase
{
    public function testProvideBypassesWhenNoObjectMapper(): void
    {
        $data = new SourceEntity();
        $operation = new Get(class: TargetResource::class);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);
        $provider = new ObjectMapperProvider(null, $decorated);

        $result = $provider->provide($operation);
        $this->assertSame($data, $result);
    }

    public function testProvideBypassesWhenOperationCannotMap(): void
    {
        $data = new SourceEntity();
        $operation = new Get(class: TargetResource::class, map: false);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertSame($data, $result);
    }

    public function testProvideBypassesWhenDataIsNull(): void
    {
        $operation = new Get(class: TargetResource::class, map: true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertNull($result);
    }

    public function testProvideMapsObject(): void
    {
        $sourceEntity = new SourceEntity();
        $targetResource = new TargetResource();
        $operation = new Get(class: TargetResource::class, map: true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($sourceEntity, TargetResource::class)
            ->willReturn($targetResource);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($sourceEntity);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertSame($targetResource, $result);
    }

    public function testProvideMapsObjectAndSetsRequestAttributes(): void
    {
        $sourceEntity = new SourceEntity();
        $targetResource = new TargetResource();
        $operation = new Get(class: TargetResource::class, map: true);
        $request = new Request();
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($sourceEntity, TargetResource::class)
            ->willReturn($targetResource);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($sourceEntity);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation, [], ['request' => $request]);
        $this->assertSame($targetResource, $result);
        $this->assertSame($sourceEntity, $request->attributes->get('mapped_data'));
        $this->assertSame($targetResource, $request->attributes->get('data'));
        $this->assertInstanceOf(TargetResource::class, $request->attributes->get('previous_data'));
        $this->assertNotSame($targetResource, $request->attributes->get('previous_data'));
    }

    public function testProvideMapsArray(): void
    {
        $sourceEntity1 = new SourceEntity();
        $sourceEntity2 = new SourceEntity();
        $targetResource1 = new TargetResource();
        $targetResource2 = new TargetResource();
        $operation = new Get(class: TargetResource::class, map: true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->exactly(2))
            ->method('map')
            ->willReturnCallback(function ($source, $target) use ($sourceEntity1, $sourceEntity2, $targetResource1, $targetResource2) {
                if ($source === $sourceEntity1) {
                    return $targetResource1;
                }
                if ($source === $sourceEntity2) {
                    return $targetResource2;
                }

                return null;
            });
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([$sourceEntity1, $sourceEntity2]);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($targetResource1, $result[0]);
        $this->assertSame($targetResource2, $result[1]);
    }

    public function testProvideMapsPaginator(): void
    {
        $sourceEntity1 = new SourceEntity();
        $sourceEntity2 = new SourceEntity();
        $targetResource1 = new TargetResource();
        $targetResource2 = new TargetResource();
        $operation = new Get(class: TargetResource::class, map: true);
        $paginator = new ArrayPaginator([$sourceEntity1, $sourceEntity2], 0, 10);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->exactly(2))
            ->method('map')
            ->willReturnCallback(function ($source, $target) use ($sourceEntity1, $sourceEntity2, $targetResource1, $targetResource2) {
                if ($source === $sourceEntity1) {
                    return $targetResource1;
                }
                if ($source === $sourceEntity2) {
                    return $targetResource2;
                }

                return null;
            });
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($paginator);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertInstanceOf(ArrayPaginator::class, $result);
        $items = iterator_to_array($result);
        $this->assertCount(2, $items);
        $this->assertSame($targetResource1, $items[0]);
        $this->assertSame($targetResource2, $items[1]);
    }

    public function testProvideMapsEmptyArray(): void
    {
        $operation = new Get(class: TargetResource::class, map: true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testProvideMapsEmptyPaginator(): void
    {
        $operation = new Get(class: TargetResource::class, map: true);
        $paginator = new ArrayPaginator([], 0, 10);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($paginator);
        $provider = new ObjectMapperProvider($objectMapper, $decorated);

        $result = $provider->provide($operation);
        $this->assertInstanceOf(ArrayPaginator::class, $result);
        $this->assertCount(0, iterator_to_array($result));
    }
}

class SourceEntity
{
    public string $name = 'source';
}

class TargetResource
{
    public string $name = 'target';
}
