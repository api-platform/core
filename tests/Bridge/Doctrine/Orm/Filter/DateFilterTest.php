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

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class DateFilterTest extends KernelTestCase
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    protected $resourceClass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $manager->getRepository(Dummy::class);
        $this->resourceClass = Dummy::class;
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply($properties, array $filterParameters, string $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $filterParameters);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new DateFilter(
            $this->managerRegistry,
            $requestStack,
            null,
            $properties
        );

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass);
        $actual = $queryBuilder->getQuery()->getDQL();

        $this->assertEquals($expected, $actual);
    }

    public function testGetDescription()
    {
        $filter = new DateFilter(
            $this->managerRegistry,
            new RequestStack()
        );

        $this->assertEquals([
            'dummyDate[before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    /**
     * Provides test data.
     *
     * Provides 3 parameters:
     *  - configuration of filterable properties
     *  - filter parameters
     *  - expected DQL query
     *
     * @return array
     */
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
            'before (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1', Dummy::class),
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
        ];
    }
}
