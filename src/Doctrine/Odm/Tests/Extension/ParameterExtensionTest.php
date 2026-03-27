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

namespace ApiPlatform\Doctrine\Odm\Tests\Extension;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\NameConverterAwareInterface;
use ApiPlatform\Doctrine\Odm\Extension\ParameterExtension;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ParameterExtensionTest extends TestCase
{
    public function testApplyToCollectionWithNoParameters(): void
    {
        $aggregationBuilder = $this->createMock(Builder::class);
        $operation = new GetCollection();
        $extension = new ParameterExtension($this->createNonCalledFilterLocator());

        $context = [];
        $extension->applyToCollection($aggregationBuilder, 'SomeClass', $operation, $context);

        $this->assertSame([], $context);
    }

    public function testApplyToCollectionWithParameterAndFilter(): void
    {
        $filterLocator = $this->createMock(ContainerInterface::class);
        $filterLocator->expects($this->once())->method('has')
            ->with('filter_service_id')
            ->willReturn(true);
        $filterLocator->expects($this->once())->method('get')
            ->with('filter_service_id')
            ->willReturn($this->createFilterMock());

        $aggregationBuilder = $this->createMock(Builder::class);
        $operation = (new GetCollection())
            ->withParameters([
                (new QueryParameter(
                    key: 'param1',
                    filter: $this->createFilterMock(),
                ))->setValue(1),
                (new QueryParameter(
                    key: 'param2',
                    filter: 'filter_service_id' // From the container
                ))->setValue(2),
                new QueryParameter(
                    key: 'param3',
                    // Filer not called because no value
                    filter: $this->createFilterMock(false)
                ),
                new QueryParameter(
                    key: 'param4',
                    // Filer not called because no value
                    filter: 'filter_service_id_not_called'
                ),
            ]);
        $extension = new ParameterExtension($filterLocator);

        $context = [];
        $extension->applyToCollection($aggregationBuilder, 'SomeClass', $operation, $context);

        $this->assertSame([], $context);
    }

    public function testApplyToCollectionWithLoggerAndManagerRegistry(): void
    {
        $aggregationBuilder = $this->createMock(Builder::class);

        $filter = new class implements FilterInterface, LoggerAwareInterface, ManagerRegistryAwareInterface {
            use BackwardCompatibleFilterDescriptionTrait;
            use LoggerAwareTrait;
            use ManagerRegistryAwareTrait;

            public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
            {
                Assert::assertNotNull($this->logger);
                Assert::assertNotNull($this->managerRegistry);
                Assert::assertSame('SomeClass', $resourceClass);
            }
        };

        $operation = (new GetCollection())
            ->withParameters([
                (new QueryParameter(
                    key: 'param1',
                    filter: $filter,
                ))->setValue(1),
            ]);

        $extension = new ParameterExtension(
            $this->createNonCalledFilterLocator(),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(LoggerInterface::class),
        );
        $context = [];
        $extension->applyToCollection($aggregationBuilder, 'SomeClass', $operation, $context);

        $this->assertSame([], $context);
        $this->assertNotNull($filter->getLogger());
        $this->assertNotNull($filter->getManagerRegistry());
    }

    public function testApplyToCollectionWithNameConverter(): void
    {
        $aggregationBuilder = $this->createMock(Builder::class);
        $nameConverter = $this->createMock(NameConverterInterface::class);

        $filter = new class($nameConverter) implements FilterInterface, NameConverterAwareInterface {
            use BackwardCompatibleFilterDescriptionTrait;

            private ?NameConverterInterface $nameConverter = null;

            public function __construct(private readonly NameConverterInterface $expectedNameConverter)
            {
            }

            public function hasNameConverter(): bool
            {
                return $this->nameConverter instanceof NameConverterInterface;
            }

            public function getNameConverter(): ?NameConverterInterface
            {
                return $this->nameConverter;
            }

            public function setNameConverter(NameConverterInterface $nameConverter): void
            {
                $this->nameConverter = $nameConverter;
            }

            public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
            {
                Assert::assertTrue($this->hasNameConverter());
                Assert::assertSame($this->expectedNameConverter, $this->getNameConverter());
                Assert::assertSame('SomeClass', $resourceClass);
            }
        };

        $operation = (new GetCollection())
            ->withParameters([
                (new QueryParameter(
                    key: 'param1',
                    filter: $filter,
                ))->setValue(1),
            ]);

        $extension = new ParameterExtension(
            $this->createNonCalledFilterLocator(),
            nameConverter: $nameConverter,
        );
        $context = [];
        $extension->applyToCollection($aggregationBuilder, 'SomeClass', $operation, $context);

        $this->assertSame([], $context);
        $this->assertSame($nameConverter, $filter->getNameConverter());
    }

    public function testApplyToCollectionPassesContext(): void
    {
        $aggregationBuilder = $this->createMock(Builder::class);

        $filter = new class implements FilterInterface {
            use BackwardCompatibleFilterDescriptionTrait;

            public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
            {
                Assert::assertIsArray($context['filters']);
                Assert::assertInstanceOf(Parameter::class, $context['parameter']);
                $context['check_the_filters'][] = $context['filters'];
            }
        };

        $operation = (new GetCollection())
            ->withParameters([
                (new QueryParameter(
                    key: 'param1',
                    filter: $filter,
                ))->setValue(1),
                (new QueryParameter(
                    key: 'param2',
                    filter: $filter,
                ))->setValue(2),
            ]);

        $extension = new ParameterExtension($this->createNonCalledFilterLocator());
        $context = [];
        $extension->applyToCollection($aggregationBuilder, 'SomeClass', $operation, $context);

        $this->assertSame([
            'check_the_filters' => [
                ['param1' => 1],
                ['param2' => 2],
            ],
        ], $context);
    }

    private function createFilterMock(bool $expectCall = true): FilterInterface
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($expectCall ? $this->once() : $this->never())
            ->method('apply');

        return $filter;
    }

    private function createNonCalledFilterLocator(): ContainerInterface
    {
        $filterLocator = $this->createMock(ContainerInterface::class);
        $filterLocator->expects($this->never())->method('has');
        $filterLocator->expects($this->never())->method('get');

        return $filterLocator;
    }
}
