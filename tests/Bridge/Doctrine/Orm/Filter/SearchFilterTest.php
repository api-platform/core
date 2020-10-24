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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\SearchFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class SearchFilterTest extends DoctrineOrmFilterTestCase
{
    use ProphecyTrait;
    use SearchFilterTestTrait;

    protected $alias = 'oo';
    protected $filterClass = SearchFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildSearchFilter($this->managerRegistry);

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
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
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

    public function testDoubleJoin()
    {
        $this->doTestDoubleJoin(false);
    }

    /**
     * @group legacy
     */
    public function testRequestDoubleJoin()
    {
        $this->doTestDoubleJoin(true);
    }

    private function doTestDoubleJoin(bool $request)
    {
        $filters = ['relatedDummy.symfony' => 'foo'];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder($this->alias);
        $filter = $this->buildSearchFilter($this->managerRegistry, ['relatedDummy.symfony' => null], $requestStack);

        $queryBuilder->innerJoin(sprintf('%s.relatedDummy', $this->alias), 'relateddummy_a1');
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op', $request ? [] : ['filters' => $filters]);

        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s inner join %1$s.relatedDummy relateddummy_a1 WHERE relateddummy_a1.symfony = :symfony_p1', $this->alias, Dummy::class));
        $this->assertEquals($actual, $expected);
    }

    public function testTripleJoin()
    {
        $this->doTestTripleJoin(false);
    }

    /**
     * @group legacy
     */
    public function testRequestTripleJoin()
    {
        $this->doTestTripleJoin(true);
    }

    private function doTestTripleJoin(bool $request)
    {
        $filters = ['relatedDummy.symfony' => 'foo', 'relatedDummy.thirdLevel.level' => '2'];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder($this->alias);
        $filter = $this->buildSearchFilter($this->managerRegistry, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null], $requestStack);

        $queryBuilder->innerJoin(sprintf('%s.relatedDummy', $this->alias), 'relateddummy_a1');
        $queryBuilder->innerJoin('relateddummy_a1.thirdLevel', 'thirdLevel_a1');

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op', $request ? [] : ['filters' => $filters]);
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s inner join %1$s.relatedDummy relateddummy_a1 inner join relateddummy_a1.thirdLevel thirdLevel_a1 WHERE relateddummy_a1.symfony = :symfony_p1 and thirdLevel_a1.level = :level_p2', $this->alias, Dummy::class));
        $this->assertEquals($actual, $expected);
    }

    public function testJoinLeft()
    {
        $this->doTestJoinLeft(false);
    }

    /**
     * @group legacy
     */
    public function testRequestJoinLeft()
    {
        $this->doTestJoinLeft(true);
    }

    private function doTestJoinLeft(bool $request)
    {
        $filters = ['relatedDummy.symfony' => 'foo', 'relatedDummy.thirdLevel.level' => '3'];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder($this->alias);
        $queryBuilder->leftJoin(sprintf('%s.relatedDummy', $this->alias), 'relateddummy_a1');

        $filter = $this->buildSearchFilter($this->managerRegistry, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null], $requestStack);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op', $request ? [] : ['filters' => $filters]);

        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower(sprintf('SELECT %s FROM %s %1$s left join %1$s.relatedDummy relateddummy_a1 left join relateddummy_a1.thirdLevel thirdLevel_a1 WHERE relateddummy_a1.symfony = :symfony_p1 and thirdLevel_a1.level = :level_p2', $this->alias, Dummy::class));
        $this->assertEquals($actual, $expected);
    }

    public function testApplyWithAnotherAlias()
    {
        $this->doTestApplyWithAnotherAlias(false);
    }

    /**
     * @group legacy
     */
    public function testRequestApplyWithAnotherAlias()
    {
        $this->doTestApplyWithAnotherAlias(true);
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting ItemIdentifiersExtractor is deprecated since API Platform 2.5 and can lead to unexpected behaviors, it will not be possible anymore in API Platform 3.0.
     */
    public function testNotPassingIdentifiersExtractor()
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/api/dummies', 'GET', []));
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = self::$kernel->getContainer()->get('test.property_accessor');

        return new SearchFilter($this->managerRegistry, $requestStack, $iriConverter, $propertyAccessor, null, null, null);
    }

    private function doTestApplyWithAnotherAlias(bool $request)
    {
        $filters = ['name' => 'exact'];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder('somealias');

        $filter = $this->buildSearchFilter($this->managerRegistry, ['id' => null, 'name' => null], $requestStack);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op', $request ? [] : ['filters' => $filters]);

        $expectedDql = sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', 'somealias', Dummy::class);
        $this->assertEquals($expectedDql, $queryBuilder->getQuery()->getDQL());
    }

    public function provideApplyTestData(): array
    {
        $filterFactory = [$this, 'buildSearchFilter'];

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'exact' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                    ['name_p1' => 'exact'],
                    $filterFactory,
                ],
                'exact (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) = LOWER(:name_p1)', $this->alias, Dummy::class),
                    ['name_p1' => 'exact'],
                    $filterFactory,
                ],
                'exact (multiple values)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name IN (:name_p1)', $this->alias, Dummy::class),
                    [
                        'name_p1' => [
                            'CaSE',
                            'SENSitive',
                        ],
                    ],
                    $filterFactory,
                ],
                'exact (multiple values; case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) IN (:name_p1)', $this->alias, Dummy::class),
                    [
                        'name_p1' => [
                            'case',
                            'insensitive',
                        ],
                    ],
                    $filterFactory,
                ],
                'invalid property' => [
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'invalid values for relations' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'partial' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1, \'%%\')', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'partial (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1, \'%%\'))', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'start' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1, \'%%\')', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'start (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1, \'%%\'))', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'end' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1)', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'end (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1))', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'word_start' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p1, \'%%\')', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'word_start (case insensitive)' => [
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p1, \'%%\'))', $this->alias, Dummy::class),
                    ['name_p1' => 'partial'],
                    $filterFactory,
                ],
                'invalid value for relation' => [
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
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
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'IRI value for relation' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', $this->alias, Dummy::class),
                    ['id_p1' => 1],
                    $filterFactory,
                ],
                'mixed IRI and entity ID values for relations' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummies relatedDummies_a1 WHERE %1$s.relatedDummy IN (:relatedDummy_p1) AND relatedDummies_a1.id = :relatedDummies_p2', $this->alias, Dummy::class),
                    [
                        'relatedDummy_p1' => [1, 2],
                        'relatedDummies_p2' => 1,
                    ],
                    $filterFactory,
                ],
                'nested property' => [
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE %1$s.name = :name_p1 AND relatedDummy_a1.symfony = :symfony_p2', $this->alias, Dummy::class),
                    [
                        'name_p1' => 'exact',
                        'symfony_p2' => 'exact',
                    ],
                    $filterFactory,
                ],
                // Additional tests for property aliasing and explicit strategies
                'aliased property' => [
                    [
                        'id' => null,
                        'aliasedName' => ['property' => 'name', 'defaultStrategy' => 'exact'],
                    ],
                    [
                        'aliasedName' => 'test',
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                    ['name_p1' => 'test'],
                    $filterFactory,
                ],
                'aliased property (with explicit strategy)' => [
                    [
                        'id' => null,
                        'aliasedName' => ['property' => 'name', 'defaultStrategy' => 'exact'],
                    ],
                    [
                        'aliasedName' => ['exact' => 'test'],
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                    ['name_p1' => 'test'],
                    $filterFactory,
                ],
                'aliased property (with multiple explicit strategies)' => [
                    [
                        'id' => null,
                        'aliasedName' => ['property' => 'name', 'defaultStrategy' => 'exact'],
                    ],
                    [
                        'aliasedName' => ['partial' => 'test_partial', 'start' => 'test_start', 'end' => 'test_end', 'word_start' => 'test_word_start'],
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1, \'%%\') AND %1$s.name LIKE CONCAT(:name_p2, \'%%\') AND %1$s.name LIKE CONCAT(\'%%\', :name_p3) AND (%1$s.name LIKE CONCAT(:name_p4, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p4, \'%%\'))', $this->alias, Dummy::class),
                    ['name_p1' => 'test_partial', 'name_p2' => 'test_start', 'name_p3' => 'test_end', 'name_p4' => 'test_word_start'],
                    $filterFactory,
                ],
                'aliased property (with multiple explicit strategies lowercase)' => [
                    [
                        'id' => null,
                        'aliasedName' => ['property' => 'name', 'defaultStrategy' => 'exact'],
                    ],
                    [
                        'aliasedName' => ['ipartial' => 'test_partial', 'istart' => 'test_start', 'iend' => 'test_end', 'iword_start' => 'test_word_start'],
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1, \'%%\')) AND LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p2, \'%%\')) AND LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p3)) AND (LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p4, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p4, \'%%\')))', $this->alias, Dummy::class),
                    ['name_p1' => 'test_partial', 'name_p2' => 'test_start', 'name_p3' => 'test_end', 'name_p4' => 'test_word_start'],
                    $filterFactory,
                ],
                'aliased property (original property name)' => [
                    [
                        'id' => null,
                        'aliasedName' => ['property' => 'name', 'defaultStrategy' => 'exact'],
                    ],
                    [
                        'name' => ['partial' => 'test_partial', 'start' => 'test_start'],
                    ],
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'aliased property (IRI value for relation)' => [
                    [
                        'id' => null,
                        'aliasedRelatedDummy' => ['property' => 'relatedDummy'],
                    ],
                    [
                        'aliasedRelatedDummy' => '/related_dummies/1',
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.relatedDummy = :relatedDummy_p1', $this->alias, Dummy::class),
                    ['relatedDummy_p1' => 1],
                    $filterFactory,
                ],
                'aliased property (IRI value for relation; invalid strategy)' => [
                    [
                        'id' => null,
                        'aliasedRelatedDummy' => ['property' => 'relatedDummy', 'defaultStrategy' => 'ipartial'],
                    ],
                    [
                        'aliasedRelatedDummy' => '/related_dummies/1',
                    ],
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'aliased property (IRI value for relation; invalid explicit strategy)' => [
                    [
                        'id' => null,
                        'aliasedRelatedDummy' => ['property' => 'relatedDummy'],
                    ],
                    [
                        'aliasedRelatedDummy' => ['partial' => '/related_dummies/1'],
                    ],
                    sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                    [],
                    $filterFactory,
                ],
                'aliased property (mixed IRI and entity ID values for relations)' => [
                    [
                        'id' => null,
                        'aliasedRelatedDummy' => ['property' => 'relatedDummy'],
                        'aliasedRelatedDummies' => ['property' => 'relatedDummies'],
                    ],
                    [
                        'aliasedRelatedDummy' => ['/related_dummies/1', '2'],
                        'aliasedRelatedDummies' => '1',
                    ],
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummies relatedDummies_a1 WHERE %1$s.relatedDummy IN (:relatedDummy_p1) AND relatedDummies_a1.id = :relatedDummies_p2', $this->alias, Dummy::class),
                    [
                        'relatedDummy_p1' => [1, 2],
                        'relatedDummies_p2' => 1,
                    ],
                    $filterFactory,
                ],
                'aliased property (nested)' => [
                    [
                        'id' => null,
                        'name' => 'exact',
                        'aliasedRelatedDummySymfony' => ['property' => 'relatedDummy.symfony'],
                    ],
                    [
                        'name' => 'test_name',
                        'aliasedRelatedDummySymfony' => 'test_symfony',
                    ],
                    sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE %1$s.name = :name_p1 AND relatedDummy_a1.symfony = :symfony_p2', $this->alias, Dummy::class),
                    [
                        'name_p1' => 'test_name',
                        'symfony_p2' => 'test_symfony',
                    ],
                    $filterFactory,
                ],
                'no properties specified' => [
                    null,
                    [
                        'name' => 'test_name',
                        'aliasedRelatedDummySymfony' => 'test_symfony',
                    ],
                    sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                    ['name_p1' => 'test_name'],
                    $filterFactory,
                ],
            ]
        );
    }

    protected function buildSearchFilter(ManagerRegistry $managerRegistry, ?array $properties = null, RequestStack $requestStack = null)
    {
        $relatedDummyProphecy = $this->prophesize(RelatedDummy::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getItemFromIri(Argument::type('string'), ['fetch_data' => false])->will(function ($args) use ($relatedDummyProphecy) {
            if (false !== strpos($args[0], '/related_dummies')) {
                $relatedDummyProphecy->getId()->shouldBeCalled()->willReturn(1);

                return $relatedDummyProphecy->reveal();
            }

            throw new InvalidArgumentException();
        });

        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = self::$kernel->getContainer()->get('test.property_accessor');

        $identifierExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        return new SearchFilter($managerRegistry, $requestStack, $iriConverter, $propertyAccessor, null, $properties, $identifierExtractorProphecy->reveal(), new CustomConverter());
    }
}
