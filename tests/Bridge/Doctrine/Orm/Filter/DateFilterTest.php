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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class DateFilterTest extends AbstractFilterTest
{
    protected $filterClass = DateFilter::class;

    public function testApplyDate()
    {
        $this->doTestApplyDate(false);
        $this->doTestApplyDateImmutable(false);
    }

    /**
     * @group legacy
     */
    public function testRequestApplyDate()
    {
        $this->doTestApplyDate(true);
        $this->doTestApplyDateImmutable(true);
    }

    private function doTestApplyDate(bool $request)
    {
        $filters = ['dummyDate' => ['after' => '2015-04-05']];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new DateFilter($this->managerRegistry, $requestStack, null, ['dummyDate' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), DummyDate::class, null, $request ? [] : ['filters' => $filters]);

        $this->assertEquals(new \DateTime('2015-04-05'), $queryBuilder->getParameters()[0]->getValue());
        $this->assertInstanceOf(\DateTime::class, $queryBuilder->getParameters()[0]->getValue());
    }

    private function doTestApplyDateImmutable(bool $request)
    {
        $filters = ['dummyDate' => ['after' => '2015-04-05']];

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummy_immutable_date', 'GET', $filters));
        }

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new DateFilter($this->managerRegistry, $requestStack, null, ['dummyDate' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), DummyImmutableDate::class, null, $request ? [] : ['filters' => $filters]);

        $this->assertEquals(new \DateTimeImmutable('2015-04-05'), $queryBuilder->getParameters()[0]->getValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $queryBuilder->getParameters()[0]->getValue());
    }

    public function testGetDescription()
    {
        $filter = new DateFilter($this->managerRegistry);

        $this->assertEquals([
            'dummyDate[before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return [
            'after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1', Dummy::class),
            ],
            'after but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate > :dummyDate_p1', Dummy::class),
            ],
            'after' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1', Dummy::class),
            ],
            'after but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate > :dummyDate_p1', Dummy::class),
            ],
            'before (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1', Dummy::class),
            ],
            'before but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1', Dummy::class),
            ],
            'before' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1', Dummy::class),
            ],
            'before but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1', Dummy::class),
            ],
            'before + after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1 AND o.dummyDate >= :dummyDate_p2', Dummy::class),
            ],
            'before but not equals + after but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1 AND o.dummyDate > :dummyDate_p2', Dummy::class),
            ],
            'before + after' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1 AND o.dummyDate >= :dummyDate_p2', Dummy::class),
            ],
            'before but not equals + after but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1 AND o.dummyDate > :dummyDate_p2', Dummy::class),
            ],
            'property not enabled' => [
                [
                    'unknown' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'nested property' => [
                [
                    'relatedDummy.dummyDate' => null,
                ],
                [
                    'relatedDummy.dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.dummyDate >= :dummyDate_p1', Dummy::class),
            ],
            'after (exclude_null)' => [
                [
                    'dummyDate' => 'exclude_null',
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate IS NOT NULL AND o.dummyDate >= :dummyDate_p1', Dummy::class),
            ],
            'after (include_null_after)' => [
                [
                    'dummyDate' => 'include_null_after',
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1 OR o.dummyDate IS NULL', Dummy::class),
            ],
            'bad date format' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '1932iur123ufqe',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
        ];
    }
}
