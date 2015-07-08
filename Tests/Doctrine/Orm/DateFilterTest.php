<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter;
use Doctrine\ORM\EntityRepository;
use Dunglas\ApiBundle\Api\Resource;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
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
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $manager->getRepository(Dummy::class);
        $this->resource = new Resource(Dummy::class);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testApply(array $filterParameters, array $query, $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $query);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $queryBuilder = $this->getQueryBuilder();
        $filter = new DateFilter(
            $this->managerRegistry,
            $requestStack,
            $filterParameters['properties']
        );

        $filter->apply($this->resource, $queryBuilder);
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expected = strtolower($expected);

        $this->assertEquals(
            $expected,
            $actual,
            sprintf('Expected `%s` for this `%s %s` request', $expected, 'GET', $request->getUri())
        );
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder for filters.
     */
    public function getQueryBuilder()
    {
        return $this->repository->createQueryBuilder('o');
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :date_after_dummydate',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :date_after_dummydate AND o.dummydate IS NOT NULL',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate <= :date_before_dummydate',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate <= :date_before_dummydate AND o.dummydate IS NOT NULL',
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
                'SELECT o FROM dunglas\apibundle\tests\behat\testbundle\entity\dummy o WHERE o.dummydate <= :date_before_dummydate AND o.dummydate >= :date_after_dummydate',
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
                'SELECT o FROM dunglas\apibundle\tests\behat\testbundle\entity\dummy o WHERE (o.dummydate <= :date_before_dummydate AND o.dummydate IS NOT NULL) AND (o.dummydate >= :date_after_dummydate AND o.dummydate IS NOT NULL)',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o',
            ],
        ];
    }
}
