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

namespace ApiPlatform\State\Tests\ObjectMapper;

use ApiPlatform\Metadata\SubtypeResolverInterface;
use ApiPlatform\State\ObjectMapper\SubtypeObjectMapper;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\AbstractMappedTarget;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\AmbiguousWriteSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\ConditionalWriteSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\FirstConcreteTarget;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\InterfaceAndParentSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\ReadableSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\SecondConcreteTarget;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\ServiceConditionSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\SportsCarSource;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\TargetDeniedFromCar;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\TargetMappedFromCar;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\TargetMappedFromInterface;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\TargetMappedFromParent;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\TargetMappedFromVehicle;
use ApiPlatform\State\Tests\Fixtures\ObjectMapper\UnmappedSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class SubtypeObjectMapperTest extends TestCase
{
    public function testAmbiguousWriteTargets(): void
    {
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->never())->method('map');
        $resolver = $this->createStub(SubtypeResolverInterface::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('"%s", "%s"', FirstConcreteTarget::class, SecondConcreteTarget::class));

        $this->createMapper($decorated, $resolver)->map(new AmbiguousWriteSource(), AbstractMappedTarget::class);
    }

    public function testNoSubclassMapsFromSource(): void
    {
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->never())->method('map');
        $resolver = $this->createStub(SubtypeResolverInterface::class);
        $resolver->method('getSubtypes')->willReturn([TargetMappedFromVehicle::class, TargetMappedFromCar::class]);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Cannot map "%s" to the abstract class "%s": no subclass of "%s" maps from it.', UnmappedSource::class, AbstractMappedTarget::class, AbstractMappedTarget::class));

        $this->createMapper($decorated, $resolver)->map(new UnmappedSource(), AbstractMappedTarget::class);
    }

    public function testClosestParentSourceWins(): void
    {
        $source = new SportsCarSource();
        $expected = new TargetMappedFromCar();
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->once())->method('map')->with($source, TargetMappedFromCar::class)->willReturn($expected);
        $resolver = $this->createStub(SubtypeResolverInterface::class);
        $resolver->method('getSubtypes')->willReturn([TargetMappedFromVehicle::class, TargetMappedFromCar::class]);

        $this->assertSame($expected, $this->createMapper($decorated, $resolver)->map($source, AbstractMappedTarget::class));
    }

    public function testInterfaceAndParentSourcesAreAmbiguous(): void
    {
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->never())->method('map');
        $resolver = $this->createStub(SubtypeResolverInterface::class);
        $resolver->method('getSubtypes')->willReturn([TargetMappedFromInterface::class, TargetMappedFromParent::class]);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('"%s", "%s"', TargetMappedFromInterface::class, TargetMappedFromParent::class));

        $this->createMapper($decorated, $resolver)->map(new InterfaceAndParentSource(), AbstractMappedTarget::class);
    }

    public function testFalseConditionExcludesWriteCandidate(): void
    {
        $source = new ConditionalWriteSource();
        $expected = new SecondConcreteTarget();
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->once())->method('map')->with($source, SecondConcreteTarget::class)->willReturn($expected);

        $this->assertSame($expected, $this->createMapper($decorated, $this->createStub(SubtypeResolverInterface::class))->map($source, AbstractMappedTarget::class));
    }

    public function testFalseConditionExcludesReadCandidate(): void
    {
        $source = new SportsCarSource();
        $expected = new TargetMappedFromVehicle();
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->once())->method('map')->with($source, TargetMappedFromVehicle::class)->willReturn($expected);
        $resolver = $this->createStub(SubtypeResolverInterface::class);
        $resolver->method('getSubtypes')->willReturn([TargetMappedFromVehicle::class, TargetDeniedFromCar::class]);

        $this->assertSame($expected, $this->createMapper($decorated, $resolver)->map($source, AbstractMappedTarget::class));
    }

    public function testServiceConditionIsRejected(): void
    {
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->never())->method('map');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Cannot evaluate the "if" condition of the mapping from "%s" to "%s": service conditions are not supported when mapping to an abstract class.', ServiceConditionSource::class, FirstConcreteTarget::class));

        $this->createMapper($decorated, $this->createStub(SubtypeResolverInterface::class))->map(new ServiceConditionSource(), AbstractMappedTarget::class);
    }

    public function testResolutionIsCached(): void
    {
        $metadata = $this->createMock(ObjectMapperMetadataFactoryInterface::class);
        $metadata->expects($this->once())->method('create')->willReturn([new Mapping(FirstConcreteTarget::class)]);
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->exactly(2))->method('map')->with($this->anything(), FirstConcreteTarget::class)->willReturn(new FirstConcreteTarget());

        $mapper = new SubtypeObjectMapper($decorated, $this->createStub(SubtypeResolverInterface::class), $metadata);
        $mapper->map(new UnmappedSource(), AbstractMappedTarget::class);
        $mapper->map(new UnmappedSource(), AbstractMappedTarget::class);
    }

    public function testDynamicConditionIsEvaluatedOnEveryCall(): void
    {
        $state = new \ArrayObject(['allowFirst' => true]);
        $metadata = $this->createStub(ObjectMapperMetadataFactoryInterface::class);
        $metadata->method('create')->willReturn([
            new Mapping(FirstConcreteTarget::class, if: static fn (): bool => $state['allowFirst']),
            new Mapping(SecondConcreteTarget::class, if: static fn (): bool => !$state['allowFirst']),
        ]);
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->exactly(2))->method('map')->willReturnCallback(static fn (object $source, string $target): object => new $target());

        $mapper = new SubtypeObjectMapper($decorated, $this->createStub(SubtypeResolverInterface::class), $metadata);
        $this->assertSame(FirstConcreteTarget::class, $mapper->map(new UnmappedSource(), AbstractMappedTarget::class)::class);

        $state['allowFirst'] = false;
        $this->assertSame(SecondConcreteTarget::class, $mapper->map(new UnmappedSource(), AbstractMappedTarget::class)::class);
    }

    public function testInterfaceTargetIsNamedInTheMessage(): void
    {
        $decorated = $this->createMock(ObjectMapperInterface::class);
        $decorated->expects($this->never())->method('map');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Cannot map "%s" to the interface "%s"', UnmappedSource::class, ReadableSource::class));

        $this->createMapper($decorated, $this->createStub(SubtypeResolverInterface::class))->map(new UnmappedSource(), ReadableSource::class);
    }

    private function createMapper(ObjectMapperInterface $decorated, SubtypeResolverInterface $resolver): SubtypeObjectMapper
    {
        return new SubtypeObjectMapper($decorated, $resolver, new ReflectionObjectMapperMetadataFactory());
    }
}
