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

namespace ApiPlatform\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\SearchFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class SearchFilterTest extends DoctrineOrmFilterTestCase
{
    use ProphecyTrait;
    use SearchFilterTestTrait;

    protected const ALIAS = 'oo';
    protected string $filterClass = SearchFilter::class;

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = self::buildSearchFilter($this, $this->managerRegistry);

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'description' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'description[]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => \DateTimeInterface::class,
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => \DateTimeInterface::class,
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'arrayData' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'arrayData[]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'name_converted' => [
                'property' => 'name_converted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'name_converted[]' => [
                'property' => 'name_converted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyBoolean[]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testDoubleJoin(): void
    {
        $filters = ['relatedDummy.symfony' => 'foo'];

        $queryBuilder = $this->repository->createQueryBuilder(static::ALIAS);
        $filter = self::buildSearchFilter($this, $this->managerRegistry, ['relatedDummy.symfony' => null]);

        $queryBuilder->innerJoin(sprintf('%s.relatedDummy', static::ALIAS), 'relateddummy_a1');
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, new Get(), ['filters' => $filters]);

        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s inner join %1$s.relatedDummy relateddummy_a1 WHERE relateddummy_a1.symfony = :symfony_p1', static::ALIAS, Dummy::class));
        $this->assertSame($actual, $expected);
    }

    public function testTripleJoin(): void
    {
        $filters = ['relatedDummy.symfony' => 'foo', 'relatedDummy.thirdLevel.level' => '2'];

        $queryBuilder = $this->repository->createQueryBuilder(static::ALIAS);
        $filter = self::buildSearchFilter($this, $this->managerRegistry, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null]);

        $queryBuilder->innerJoin(sprintf('%s.relatedDummy', static::ALIAS), 'relateddummy_a1');
        $queryBuilder->innerJoin('relateddummy_a1.thirdLevel', 'thirdLevel_a1');

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, new Get(), ['filters' => $filters]);
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s inner join %1$s.relatedDummy relateddummy_a1 inner join relateddummy_a1.thirdLevel thirdLevel_a1 WHERE relateddummy_a1.symfony = :symfony_p1 and thirdLevel_a1.level = :level_p2', static::ALIAS, Dummy::class));
        $this->assertSame($actual, $expected);
    }

    public function testJoinLeft(): void
    {
        $filters = ['relatedDummy.symfony' => 'foo', 'relatedDummy.thirdLevel.level' => '3'];

        $queryBuilder = $this->repository->createQueryBuilder(static::ALIAS);
        $queryBuilder->leftJoin(sprintf('%s.relatedDummy', static::ALIAS), 'relateddummy_a1');

        $filter = self::buildSearchFilter($this, $this->managerRegistry, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, new Get(), ['filters' => $filters]);

        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s left join %1$s.relatedDummy relateddummy_a1 left join relateddummy_a1.thirdLevel thirdLevel_a1 WHERE relateddummy_a1.symfony = :symfony_p1 and thirdLevel_a1.level = :level_p2', static::ALIAS, Dummy::class));
        $this->assertSame($actual, $expected);
    }

    public function testApplyWithAnotherAlias(): void
    {
        $filters = ['name' => 'exact'];

        $queryBuilder = $this->repository->createQueryBuilder('somealias');

        $filter = self::buildSearchFilter($this, $this->managerRegistry, ['id' => null, 'name' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, new Get(), ['filters' => $filters]);

        $expectedDql = sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', 'somealias', Dummy::class);
        $this->assertSame($expectedDql, $queryBuilder->getQuery()->getDQL());
    }

    public static function provideApplyTestData(): array
    {
        $filterFactory = self::buildSearchFilter(...);

        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'exact' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', static::ALIAS, Dummy::class),
                    ['name_p1' => 'exact'],
                    $filterFactory,
                ],
                'exact (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) = LOWER(:name_p1)', static::ALIAS, Dummy::class),
                    ['name_p1' => 'exact'],
                    $filterFactory,
                ],
                'exact (case insensitive, with special characters)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) = LOWER(:name_p1)', static::ALIAS, Dummy::class),
                    ['name_p1' => 'exact (special)'],
                    $filterFactory,
                ],
                'exact (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name IN(:name_p1)', static::ALIAS, Dummy::class),
                    [
                        'name_p1' => [
                            'CaSE',
                            'SENSitive',
                        ],
                    ],
                    $filterFactory,
                ],
                'exact (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) IN(:name_p1)', static::ALIAS, Dummy::class),
                    [
                        'name_p1' => [
                            'case',
                            'insensitive',
                        ],
                    ],
                    $filterFactory,
                ],
                'invalid property' => [
                    sprintf('SELECT %s FROM %s %1$s', static::ALIAS, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'invalid values for relations' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', static::ALIAS, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'partial' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1_0, \'%%\')', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'partial (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_0, \'%%\'))', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'partial (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1_0, \'%%\') OR %1$s.name LIKE CONCAT(\'%%\', :name_p1_1, \'%%\')', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'CaSE',
                        'name_p1_1' => 'SENSitive',
                    ],
                    $filterFactory,
                ],
                'partial (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_0, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_1, \'%%\'))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'case',
                        'name_p1_1' => 'insensitive',
                    ],
                    $filterFactory,
                ],
                'partial (multiple almost same values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_0, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_1, \'%%\'))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'blue car',
                        'name_p1_1' => 'blue car',
                    ],
                    $filterFactory,
                ],
                'start' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1_0, \'%%\')', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'start (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_0, \'%%\'))', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'start (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1_0, \'%%\') OR %1$s.name LIKE CONCAT(:name_p1_1, \'%%\')', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'CaSE',
                        'name_p1_1' => 'SENSitive',
                    ],
                    $filterFactory,
                ],
                'start (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_0, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_1, \'%%\'))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'case',
                        'name_p1_1' => 'insensitive',
                    ],
                    $filterFactory,
                ],
                'end' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1_0)', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'end (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_0))', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'end (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1_0) OR %1$s.name LIKE CONCAT(\'%%\', :name_p1_1)', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'CaSE',
                        'name_p1_1' => 'SENSitive',
                    ],
                    $filterFactory,
                ],
                'end (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_0)) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1_1))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'case',
                        'name_p1_1' => 'insensitive',
                    ],
                    $filterFactory,
                ],
                'word_start' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1_0, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p1_0, \'%%\')', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'word_start (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_0, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p1_0, \'%%\'))', static::ALIAS, Dummy::class),
                    ['name_p1_0' => 'partial'],
                    $filterFactory,
                ],
                'word_start (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE (%1$s.name LIKE CONCAT(:name_p1_0, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p1_0, \'%%\')) OR (%1$s.name LIKE CONCAT(:name_p1_1, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p1_1, \'%%\'))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'CaSE',
                        'name_p1_1' => 'SENSitive',
                    ],
                    $filterFactory,
                ],
                'word_start (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE (LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_0, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p1_0, \'%%\'))) OR (LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1_1, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p1_1, \'%%\')))', static::ALIAS, Dummy::class),
                    [
                        'name_p1_0' => 'case',
                        'name_p1_1' => 'insensitive',
                    ],
                    $filterFactory,
                ],
                'invalid value for relation' => [
                    sprintf('SELECT %s FROM %s %1$s', static::ALIAS, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'invalid iri for relation' => [
                    [
                        'id' => null,
                        'name' => null,
                        'relatedDummy' => null,
                    ],
                    [
                        'relatedDummy' => '/related_dummie/1',
                    ],
                    sprintf('SELECT %s FROM %s %1$s', static::ALIAS, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'IRI value for relation' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', static::ALIAS, Dummy::class),
                    ['id_p1' => 1],
                    $filterFactory,
                ],
                'mixed IRI and entity ID values for relations' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummies relatedDummies_a1 WHERE %1$s.relatedDummy IN(:relatedDummy_p1) AND relatedDummies_a1.id = :id_p2', static::ALIAS, Dummy::class),
                    [
                        'relatedDummy_p1' => [1, 2],
                        'id_p2' => 1,
                    ],
                    $filterFactory,
                ],
                'nested property' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE %1$s.name = :name_p1 AND relatedDummy_a1.symfony = :symfony_p2', static::ALIAS, Dummy::class),
                    [
                        'name_p1' => 'exact',
                        'symfony_p2' => 'exact',
                    ],
                    $filterFactory,
                ],
                'empty nested property' => [
                    sprintf('SELECT %s FROM %s %1$s', static::ALIAS, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'integer value' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.age = :age_p1', static::ALIAS, RelatedDummy::class),
                    ['age_p1' => 46],
                    $filterFactory,
                    RelatedDummy::class,
                ],
                'related owned one-to-one association' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1.id = :id_p1', static::ALIAS, Dummy::class),
                    ['id_p1' => 1],
                    $filterFactory,
                    Dummy::class,
                ],
                'related owning one-to-one association' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.relatedOwningDummy = :relatedOwningDummy_p1', static::ALIAS, Dummy::class),
                    ['relatedOwningDummy_p1' => 1],
                    $filterFactory,
                    Dummy::class,
                ],
            ]
        );
    }

    protected static function buildSearchFilter(self $that, ManagerRegistry $managerRegistry, ?array $properties = null): SearchFilter
    {
        $relatedDummyProphecy = $that->prophesize(RelatedDummy::class);
        $iriConverterProphecy = $that->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getResourceFromIri(Argument::type('string'), ['fetch_data' => false])->will(function ($args) use ($relatedDummyProphecy) {
            if (str_contains((string) $args[0], '/related_dummies')) {
                $relatedDummyProphecy->getId()->shouldBeCalled()->willReturn(1);

                return $relatedDummyProphecy->reveal();
            }

            throw new InvalidArgumentException();
        });

        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = static::$kernel->getContainer()->get('test.property_accessor');

        return new SearchFilter($managerRegistry, $iriConverter, $propertyAccessor, null, $properties, null, new CustomConverter());
    }
}
