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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class SearchFilterTest extends AbstractFilterTest
{
    protected $alias = 'oo';
    protected $filterClass = SearchFilter::class;

    protected function filterFactory(ManagerRegistry $managerRegistry, RequestStack $requestStack = null, array $properties = null)
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

        return new SearchFilter($managerRegistry, $requestStack, $iriConverter, $propertyAccessor, null, $properties);
    }

    public function testGetDescription()
    {
        $filter = $this->filterFactory($this->managerRegistry, null);

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'description' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'description[]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted[]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyBoolean[]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
            ],
        ], $filter->getDescription($this->resourceClass));

        $filter = $this->filterFactory($this->managerRegistry, null, [
            'id' => null,
            'name' => null,
            'alias' => null,
            'dummy' => null,
            'dummyDate' => null,
            'jsonData' => null,
            'nameConverted' => null,
            'foo' => null,
            'relatedDummies.dummyDate' => null,
            'relatedDummy' => null,
        ]);

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'nameConverted[]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummies.dummyDate' => [
                'property' => 'relatedDummies.dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummies.dummyDate[]' => [
                'property' => 'relatedDummies.dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy[]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
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
        $filter = $this->filterFactory($this->managerRegistry, $requestStack, ['relatedDummy.symfony' => null]);

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
        $filter = $this->filterFactory($this->managerRegistry, $requestStack, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null]);

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

        $filter = $this->filterFactory($this->managerRegistry, $requestStack, ['relatedDummy.symfony' => null, 'relatedDummy.thirdLevel.level' => null]);
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

    private function doTestApplyWithAnotherAlias(bool $request)
    {
        $filters = ['name' => 'exact'];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder('somealias');

        $filter = $this->filterFactory($this->managerRegistry, $requestStack, ['id' => null, 'name' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op', $request ? [] : ['filters' => $filters]);

        $expectedDql = sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', 'somealias', Dummy::class);
        $this->assertEquals($expectedDql, $queryBuilder->getQuery()->getDQL());
    }

    public function provideApplyTestData(): array
    {
        $filterFactory = [$this, 'filterFactory'];

        return [
            'exact' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'name' => 'exact',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1', $this->alias, Dummy::class),
                ['name_p1' => 'exact'],
                $filterFactory,
            ],
            'exact (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iexact',
                ],
                [
                    'name' => 'exact',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) = LOWER(:name_p1)', $this->alias, Dummy::class),
                ['name_p1' => 'exact'],
                $filterFactory,
            ],
            'exact (multiple values)' => [
                [
                    'id' => null,
                    'name' => 'exact',
                ],
                [
                    'name' => [
                        'CaSE',
                        'SENSitive',
                    ],
                ],
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
                [
                    'id' => null,
                    'name' => 'iexact',
                ],
                [
                    'name' => [
                        'CaSE',
                        'inSENSitive',
                    ],
                ],
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
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'foo' => 'exact',
                ],
                sprintf('SELECT %s FROM %s %1$s', $this->alias, Dummy::class),
                [],
                $filterFactory,
            ],
            'invalid values for relations' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                    'relatedDummies' => null,
                ],
                [
                    'name' => ['foo'],
                    'relatedDummy' => ['foo'],
                    'relatedDummies' => [['foo']],
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name = :name_p1 AND %1$s.relatedDummy = :relatedDummy_p2', $this->alias, Dummy::class),
                ['relatedDummy_p2' => 'foo'],
                $filterFactory,
            ],
            'partial' => [
                [
                    'id' => null,
                    'name' => 'partial',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1, \'%%\')', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'partial (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'ipartial',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1, \'%%\'))', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'start' => [
                [
                    'id' => null,
                    'name' => 'start',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1, \'%%\')', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'start (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'istart',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1, \'%%\'))', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'end' => [
                [
                    'id' => null,
                    'name' => 'end',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(\'%%\', :name_p1)', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'end (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iend',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%%\', :name_p1))', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'word_start' => [
                [
                    'id' => null,
                    'name' => 'word_start',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.name LIKE CONCAT(:name_p1, \'%%\') OR %1$s.name LIKE CONCAT(\'%% \', :name_p1, \'%%\')', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'word_start (case insensitive)' => [
                [
                    'id' => null,
                    'name' => 'iword_start',
                ],
                [
                    'name' => 'partial',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE LOWER(%1$s.name) LIKE LOWER(CONCAT(:name_p1, \'%%\')) OR LOWER(%1$s.name) LIKE LOWER(CONCAT(\'%% \', :name_p1, \'%%\'))', $this->alias, Dummy::class),
                ['name_p1' => 'partial'],
                $filterFactory,
            ],
            'invalid value for relation' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                ],
                [
                    'relatedDummy' => 'exact',
                ],
                sprintf('SELECT %s FROM %s %1$s WHERE %1$s.relatedDummy = :relatedDummy_p1', $this->alias, Dummy::class),
                ['relatedDummy_p1' => 'exact'],
                $filterFactory,
            ],
            'IRI value for relation' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.id' => null,
                ],
                [
                    'relatedDummy.id' => '/related_dummies/1',
                ],
                sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', $this->alias, Dummy::class),
                ['id_p1' => 1],
                $filterFactory,
            ],
            'mixed IRI and entity ID values for relations' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy' => null,
                    'relatedDummies' => null,
                ],
                [
                    'relatedDummy' => ['/related_dummies/1', '2'],
                    'relatedDummies' => '1',
                ],
                sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummies relatedDummies_a1 WHERE %1$s.relatedDummy IN (:relatedDummy_p1) AND relatedDummies_a1.id = :relatedDummies_p2', $this->alias, Dummy::class),
                [
                    'relatedDummy_p1' => [1, 2],
                    'relatedDummies_p2' => 1,
                ],
                $filterFactory,
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.symfony' => null,
                ],
                [
                    'name' => 'exact',
                    'relatedDummy.symfony' => 'exact',
                ],
                sprintf('SELECT %s FROM %s %1$s INNER JOIN %1$s.relatedDummy relatedDummy_a1 WHERE %1$s.name = :name_p1 AND relatedDummy_a1.symfony = :symfony_p2', $this->alias, Dummy::class),
                [
                    'name_p1' => 'exact',
                    'symfony_p2' => 'exact',
                ],
                $filterFactory,
            ],
        ];
    }
}
