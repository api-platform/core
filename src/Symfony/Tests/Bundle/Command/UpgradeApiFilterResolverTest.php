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

namespace ApiPlatform\Symfony\Tests\Bundle\Command;

use ApiPlatform\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterCollisionException;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterMapper;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterNameConversionException;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterResolver;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type;

final class UpgradeApiFilterResolverTest extends TestCase
{
    /**
     * @param array<string, Type> $nativeTypes     property name => native type used to detect relations
     * @param list<string>        $resourceClasses class names the resolver should treat as API resources
     */
    private function resolver(array $nativeTypes = [], array $resourceClasses = []): UpgradeApiFilterResolver
    {
        return new UpgradeApiFilterResolver(
            new UpgradeApiFilterMapper(),
            $this->propertyMetadataFactory($nativeTypes),
            $this->resourceClassResolver($resourceClasses),
        );
    }

    /**
     * @param array<string, Type> $nativeTypes
     */
    private function propertyMetadataFactory(array $nativeTypes): PropertyMetadataFactoryInterface
    {
        return new class($nativeTypes) implements PropertyMetadataFactoryInterface {
            public function __construct(private array $nativeTypes)
            {
            }

            public function create(string $resourceClass, string $property, array $options = []): ApiProperty
            {
                return (new ApiProperty())->withNativeType($this->nativeTypes[$property] ?? Type::string());
            }
        };
    }

    /**
     * @param list<string> $resourceClasses
     */
    private function resourceClassResolver(array $resourceClasses): ResourceClassResolverInterface
    {
        return new class($resourceClasses) implements ResourceClassResolverInterface {
            public function __construct(private array $resourceClasses)
            {
            }

            public function isResourceClass(string $type): bool
            {
                return \in_array($type, $this->resourceClasses, true);
            }

            public function getResourceClass(mixed $value, ?string $resourceClass = null, bool $strict = false): string
            {
                return $resourceClass ?? '';
            }
        };
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array{filter: FilterInterface, filterClass: string, arguments: array<string, mixed>}
     */
    private function entry(string $filterClass, FilterInterface $filter, array $arguments = []): array
    {
        return ['filter' => $filter, 'filterClass' => $filterClass, 'arguments' => $arguments];
    }

    private function filter(array $description, ?array $properties = null, ?NameConverterInterface $nameConverter = null): FilterInterface
    {
        return new class($description, $properties, $nameConverter) implements FilterInterface {
            public function __construct(private array $description, private ?array $properties, private ?NameConverterInterface $nameConverter)
            {
            }

            public function getDescription(string $resourceClass): array
            {
                return $this->description;
            }

            public function getProperties(): ?array
            {
                return $this->properties;
            }

            public function getNameConverter(): ?NameConverterInterface
            {
                return $this->nameConverter;
            }
        };
    }

    public function testBooleanFilterResolvesToExact(): void
    {
        $filter = $this->filter([
            'active' => ['property' => 'active', 'type' => 'bool', 'strategy' => null],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry(BooleanFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('active', $params[0]->key);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\ExactFilter', $params[0]->filterClass);
        $this->assertSame('bool', $params[0]->nativeType);
        $this->assertTrue($params[0]->castToNativeType);
    }

    public function testSearchFilterStrategyResolvesPerProperty(): void
    {
        $filter = $this->filter([
            'name' => ['property' => 'name', 'type' => 'string', 'strategy' => 'partial'],
            'code' => ['property' => 'code', 'type' => 'string', 'strategy' => 'exact'],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry(SearchFilter::class, $filter)]);

        $byKey = [];
        foreach ($params as $p) {
            $byKey[$p->key] = $p->filterClass;
        }

        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter', $byKey['name']);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\ExactFilter', $byKey['code']);
    }

    public function testSearchFilterOnRelationResolvesToIri(): void
    {
        $filter = $this->filter([
            'groups' => ['property' => 'groups', 'type' => 'string', 'strategy' => 'exact', 'is_collection' => false],
            'groups[]' => ['property' => 'groups', 'type' => 'string', 'strategy' => 'exact', 'is_collection' => true],
        ]);

        $params = $this->resolver(
            ['groups' => Type::collection(Type::object(Collection::class), Type::object(\stdClass::class))],
            [\stdClass::class],
        )->resolve('App\Entity\User', [$this->entry(SearchFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('groups', $params[0]->key);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\IriFilter', $params[0]->filterClass);
    }

    public function testNestedSearchKeyEmitsExplicitProperty(): void
    {
        $filter = $this->filter([
            'colors.prop' => ['property' => 'colors.prop', 'type' => 'string', 'strategy' => 'ipartial'],
        ]);

        // colors.prop is a scalar reached through the colors relation, not a relation itself.
        $params = $this->resolver(['colors.prop' => Type::string()])
            ->resolve('App\Entity\DummyCar', [$this->entry(SearchFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('colors.prop', $params[0]->key);
        $this->assertSame('colors.prop', $params[0]->property);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter', $params[0]->filterClass);
    }

    public function testSearchFilterOnScalarStaysSearch(): void
    {
        $filter = $this->filter([
            'name' => ['property' => 'name', 'type' => 'string', 'strategy' => 'partial'],
        ]);

        $params = $this->resolver(['name' => Type::string()])
            ->resolve('App\Entity\Dummy', [$this->entry(SearchFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter', $params[0]->filterClass);
    }

    public function testSearchFilterOnDateFieldIsNotTreatedAsRelation(): void
    {
        $filter = $this->filter([
            'dummyDate' => ['property' => 'dummyDate', 'type' => 'string', 'strategy' => 'exact'],
        ]);

        // A \DateTime field resolves to an object native type but is not an API resource.
        $params = $this->resolver(['dummyDate' => Type::object(\DateTime::class)])
            ->resolve('App\Entity\Dummy', [$this->entry(SearchFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\ExactFilter', $params[0]->filterClass);
    }

    public function testDateFilterCarriesNullManagementAsFilterContext(): void
    {
        $filter = $this->filter([
            'dateIncludeNullAfter[before]' => ['property' => 'dateIncludeNullAfter', 'type' => 'string', 'strategy' => null],
            'dateIncludeNullAfter[after]' => ['property' => 'dateIncludeNullAfter', 'type' => 'string', 'strategy' => null],
            'plainDate[before]' => ['property' => 'plainDate', 'type' => 'string', 'strategy' => null],
        ], [
            'dateIncludeNullAfter' => DateFilterInterface::INCLUDE_NULL_AFTER,
            'plainDate' => null,
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry(DateFilter::class, $filter)]);

        $byKey = [];
        foreach ($params as $p) {
            $byKey[$p->key] = $p;
        }

        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\DateFilter', $byKey['dateIncludeNullAfter']->filterClass);
        $this->assertSame(DateFilterInterface::INCLUDE_NULL_AFTER, $byKey['dateIncludeNullAfter']->filterContext);
        $this->assertNull($byKey['plainDate']->filterContext);
    }

    public function testNameConvertedFilterIsSkipped(): void
    {
        // The new overlay filters do not denormalize, so a name-converted property cannot be migrated
        // faithfully: the resource is skipped.
        $filter = $this->filter([
            'name_converted' => ['property' => 'name_converted', 'type' => 'string', 'strategy' => 'exact'],
        ], null, new CamelCaseToSnakeCaseNameConverter());

        $this->expectException(UpgradeApiFilterNameConversionException::class);

        $this->resolver(['nameConverted' => Type::string()])
            ->resolve('App\Entity\Converted', [$this->entry(SearchFilter::class, $filter)]);
    }

    public function testFilterWithoutActualRenamingIsNotSkipped(): void
    {
        // A name converter that leaves the property unchanged (identity) must not trigger a skip.
        $filter = $this->filter([
            'name' => ['property' => 'name', 'type' => 'string', 'strategy' => 'exact'],
        ], null, new CamelCaseToSnakeCaseNameConverter());

        $params = $this->resolver(['name' => Type::string()])
            ->resolve('App\Entity\Dummy', [$this->entry(SearchFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('name', $params[0]->key);
    }

    public function testKeptCustomFilterCarriesConstructorArguments(): void
    {
        $filter = $this->filter([
            'foobargroups[]' => ['property' => null, 'type' => 'string', 'strategy' => null],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [
            $this->entry('App\Filter\GroupFilter', $filter, ['parameterName' => 'foobargroups']),
        ]);

        $this->assertCount(1, $params);
        $this->assertSame('foobargroups', $params[0]->key);
        $this->assertSame('App\Filter\GroupFilter', $params[0]->filterClass);
        $this->assertSame(['parameterName' => 'foobargroups'], $params[0]->arguments);
    }

    public function testRemappedFilterDropsConstructorArguments(): void
    {
        $filter = $this->filter([
            'active' => ['property' => 'active', 'type' => 'bool', 'strategy' => null],
        ]);

        // BooleanFilter is remapped to ExactFilter, whose constructor differs, so legacy args are dropped.
        $params = $this->resolver()->resolve('App\Entity\Dummy', [
            $this->entry(BooleanFilter::class, $filter, ['someLegacyArg' => true]),
        ]);

        $this->assertSame([], $params[0]->arguments);
    }

    public function testExistsFilterCollapsesToTemplateKey(): void
    {
        $filter = $this->filter([
            'exists[active]' => ['property' => 'active', 'type' => 'bool', 'strategy' => null],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry('App\Filter\ExistsFilter', $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('exists[:property]', $params[0]->key);
        $this->assertNull($params[0]->property);
    }

    public function testOrderFilterCollapsesToTemplateKey(): void
    {
        $filter = $this->filter([
            'order[createdAt]' => ['property' => 'createdAt', 'type' => 'string', 'strategy' => null],
            'order[name]' => ['property' => 'name', 'type' => 'string', 'strategy' => null],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry(OrderFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('order[:property]', $params[0]->key);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\SortFilter', $params[0]->filterClass);
    }

    public function testRangeOperatorKeysCollapseToBaseProperty(): void
    {
        $filter = $this->filter([
            'quantity[gt]' => ['property' => 'quantity', 'type' => 'string', 'strategy' => null],
            'quantity[lt]' => ['property' => 'quantity', 'type' => 'string', 'strategy' => null],
        ]);

        $params = $this->resolver()->resolve('App\Entity\Dummy', [$this->entry(RangeFilter::class, $filter)]);

        $this->assertCount(1, $params);
        $this->assertSame('quantity', $params[0]->key);
        $this->assertSame('ApiPlatform\Doctrine\Orm\Filter\RangeFilter', $params[0]->filterClass);
    }

    public function testCollisionOnSameKeyThrows(): void
    {
        $numeric = $this->filter([
            'quantity' => ['property' => 'quantity', 'type' => 'int', 'strategy' => null],
        ]);
        $range = $this->filter([
            'quantity[gt]' => ['property' => 'quantity', 'type' => 'string', 'strategy' => null],
        ]);

        $this->expectException(UpgradeApiFilterCollisionException::class);

        $this->resolver()->resolve('App\Entity\Dummy', [
            $this->entry(NumericFilter::class, $numeric),
            $this->entry(RangeFilter::class, $range),
        ]);
    }

    public function testCollisionWithReservedServiceFilterKeyThrows(): void
    {
        // An #[ApiFilter] SearchFilter on dummyDate would shadow an in-place service DateFilter
        // declared through the resource `filters:` array on the same query key.
        $search = $this->filter([
            'dummyDate' => ['property' => 'dummyDate', 'type' => 'string', 'strategy' => 'exact'],
        ]);
        $serviceDateFilter = $this->filter([
            'dummyDate[before]' => ['property' => 'dummyDate', 'type' => 'string', 'strategy' => null],
            'dummyDate[after]' => ['property' => 'dummyDate', 'type' => 'string', 'strategy' => null],
        ]);

        $this->expectException(UpgradeApiFilterCollisionException::class);

        $this->resolver()->resolve(
            'App\Entity\Dummy',
            [$this->entry(SearchFilter::class, $search)],
            [$serviceDateFilter],
        );
    }
}
