<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use phpmock\phpunit\PHPMock;
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
    use PHPMock;

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
     * @dataProvider filterProvider
     */
    public function testApply(array $filterParameters, array $query, $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $query);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filter = new DateFilter(
            $this->managerRegistry,
            $requestStack,
            $filterParameters['properties']
        );

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass);
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower($expected);

        $this->assertEquals(
            $expected,
            $actual,
            sprintf('Expected `%s` for this `%s %s` request', $expected, 'GET', $request->getUri())
        );
    }

    public function testGetDescription()
    {
        $filter = new DateFilter($this->managerRegistry,
            new RequestStack());
        $this->assertEquals([
            'dummyDate[before]' => [
                'property' => 'dummyDate',
                'type' => '\DateTime',
                'required' => false,
            ],
            'dummyDate[after]' => [
                'property' => 'dummyDate',
                'type' => '\DateTime',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    /**
     * Providers 3 parameters:
     *  - filter parameters.
     *  - properties to test. Keys are the property name. If the value is true, the filter should work on the property,
     *    otherwise not.
     *  - expected DQL query.
     *
     * @return array
     */
    public function filterProvider()
    {
        return [
            // Properties enabled with valid values
            // Test after
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate >= :dummydate_p1', Dummy::class),
            ],
            [
                [
                    'properties' => [
                        'dummyDate' => true,
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate >= :dummydate_p1 AND o.dummydate IS NOT NULL', Dummy::class),
            ],
            // Test before
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate <= :dummydate_p1', Dummy::class),
            ],
            [
                [
                    'properties' => [
                        'dummyDate' => true,
                    ],
                ],
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate <= :dummydate_p1 AND o.dummydate IS NOT NULL', Dummy::class),
            ],
            // with both after and before
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate <= :dummydate_p1 AND o.dummydate >= :dummydate_p2', Dummy::class),
            ],
            [
                [
                    'properties' => [
                        'dummyDate' => true,
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE (o.dummydate <= :dummydate_p1 AND o.dummydate IS NOT NULL) AND (o.dummydate >= :dummydate_p2 AND o.dummydate IS NOT NULL)', Dummy::class),
            ],
            // with no property enabled
            [
                [
                    'properties' => ['unkown'],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            // Test with association
            [
                [
                    'properties' => [
                        'relatedDummy.dummyDate' => true,
                    ],
                ],
                [
                    'relatedDummy.dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relateddummy_a1 WHERE relateddummy_a1.dummydate >= :dummydate_p1 AND relateddummy_a1.dummydate IS NOT NULL', Dummy::class),
            ],
            // Test with exclude_null
            [
                [
                    'properties' => [
                        'dummyDate' => 'exclude_null',
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate IS NOT NULL AND o.dummydate >= :dummydate_p1', Dummy::class),
            ],
            // Test with include_null_before
            [
                [
                    'properties' => [
                        'dummyDate' => 'include_null_after',
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummydate >= :dummydate_p1 OR o.dummydate IS NULL', Dummy::class),
            ],
        ];
    }
}
