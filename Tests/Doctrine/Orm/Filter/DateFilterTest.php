<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Orm\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Dunglas\ApiBundle\Api\Resource;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\DateFilter;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy;
use phpmock\phpunit\PHPMock;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
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
        $queryBuilder = $this->repository->createQueryBuilder('o');
        $filter = new DateFilter(
            $this->managerRegistry,
            $filterParameters['properties']
        );

        $uniqid = $this->getFunctionMock('Dunglas\ApiBundle\Doctrine\Orm\Util', 'uniqid');
        $uniqid->expects($this->any())->willReturn('123456abcdefg');

        $filter->apply($this->resource, $queryBuilder, $request);
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
        $filter = new DateFilter($this->managerRegistry);
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
        ], $filter->getDescription($this->resource));
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :dummydate_after_123456abcdefg',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :dummydate_after_123456abcdefg AND o.dummydate IS NOT NULL',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate <= :dummydate_before_123456abcdefg',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate <= :dummydate_before_123456abcdefg AND o.dummydate IS NOT NULL',
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
                'SELECT o FROM dunglas\apibundle\tests\behat\testbundle\entity\dummy o WHERE o.dummydate <= :dummydate_before_123456abcdefg AND o.dummydate >= :dummydate_after_123456abcdefg',
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
                'SELECT o FROM dunglas\apibundle\tests\behat\testbundle\entity\dummy o WHERE (o.dummydate <= :dummydate_before_123456abcdefg AND o.dummydate IS NOT NULL) AND (o.dummydate >= :dummydate_after_123456abcdefg AND o.dummydate IS NOT NULL)',
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
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o INNER JOIN o.relatedDummy relatedDummy_123456abcdefg WHERE relatedDummy_123456abcdefg.dummydate >= :dummydate_after_123456abcdefg AND relatedDummy_123456abcdefg.dummydate IS NOT NULL',
            ],
            // Test exclude_null
            [
                [
                    'properties' => [
                        'dummyDate' => 0,
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :dummydate_after_123456abcdefg AND o.dummydate IS NOT NULL',
            ],
            // Test with include_null_before
            [
                [
                    'properties' => [
                        'dummyDate' => 1,
                    ],
                ],
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate <= :dummydate_before_123456abcdefg OR o.dummydate IS NULL',
            ],
            // Test with include_null_after
            [
                [
                    'properties' => [
                        'dummyDate' => 2,
                    ],
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummydate >= :dummydate_after_123456abcdefg OR o.dummydate IS NULL',
            ],
        ];
    }
}
