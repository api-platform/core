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
use Dunglas\ApiBundle\Doctrine\Orm\Filter\RangeFilter;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy;
use phpmock\phpunit\PHPMock;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Lee Siong Chan <ahlee2326@me.com>
 */
class RangeFilterTest extends KernelTestCase
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
        $filter = new RangeFilter(
            $this->managerRegistry,
            $filterParameters['properties']
        );

        $uniqid = $this->getFunctionMock('Dunglas\ApiBundle\Doctrine\Orm\Util', 'uniqid');
        $uniqid->expects($this->any())->willReturn('123456abcdefg');

        try {
            $filter->apply($this->resource, $queryBuilder, $request);
        } catch (InvalidArgumentException $e) {
        }

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
        $filter = new RangeFilter($this->managerRegistry);
        $this->assertEquals([
            'id[between]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'name[between]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'alias[between]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'description[between]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[between]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[between]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[between]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[between]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[between]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
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
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'between' => '9.99..15.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice BETWEEN :dummyprice_between_123456abcdefg_1 AND :dummyPrice_between_123456abcdefg_2',
            ],
            // Invalid value
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'between' => '9.99..15.99..99.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'between' => '15.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'lt' => '9.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice < :dummyPrice_lt_123456abcdefg',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'lte' => '9.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice <= :dummyPrice_lte_123456abcdefg',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'gt' => '9.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice > :dummyPrice_gt_123456abcdefg',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'gte' => '9.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice >= :dummyPrice_gte_123456abcdefg',
            ],
            [
                [
                    'properties' => null,
                ],
                [
                    'dummyPrice' => [
                        'gte' => '9.99',
                        'lte' => '19.99',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.dummyPrice >= :dummyPrice_gte_123456abcdefg AND o.dummyPrice <= :dummyPrice_lte_123456abcdefg',
            ],
        ];
    }
}
