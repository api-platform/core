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
use Dunglas\ApiBundle\Doctrine\Orm\Filter\OrderFilter;
use phpmock\phpunit\PHPMock;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class OrderFilterTest extends KernelTestCase
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
        $class = 'Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy';
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $manager->getRepository($class);
        $this->resource = new Resource($class);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testApply(array $filterParameters, array $query, $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $query);
        $queryBuilder = $this->repository->createQueryBuilder('o');
        $parameter = (array_key_exists('parameter', $filterParameters)) ? $filterParameters['parameter'] : 'order';
        $filter = new OrderFilter(
            $this->managerRegistry,
            $parameter,
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
        $filter = new OrderFilter($this->managerRegistry, 'order', [
            'id' => null,
            'name' => null,
            'foo' => null,
        ]);
        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resource));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new OrderFilter($this->managerRegistry, 'order');
        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'order[alias]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'order[description]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummy]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'order[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'order[nameConverted]' => [
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
            // Properties enabled with valid values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.id ASC, o.name DESC',
            ],
            // Properties enabled with invalid values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'invalid',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.id ASC',
            ],
            // Properties disabled with valid values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'alias' => 'asc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.id ASC',
            ],
            // Properties disabled with invalid values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'order' => [
                        'id' => 'invalid',
                        'name' => 'asc',
                        'alias' => 'invalid',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.name ASC',
            ],
            // Unkown property disabled
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o',
            ],
            // Unkown property enabled
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'unknown' => null],
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o',
            ],
            // Test with another keyword
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                    'parameter' => 'customOrder',
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                    'customOrder' => [
                        'name' => 'desc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.name DESC',
            ],
            // Test with no list
            [
                [
                    'properties' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.id ASC, o.name ASC',
            ],
            // Related properties enabled with valid values
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy.symfony' => null],
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                        'relatedDummy.symfony' => 'desc',
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o LEFT JOIN o.relatedDummy relatedDummy_123456abcdefg ORDER BY o.id ASC, o.name DESC, relatedDummy_123456abcdefg.symfony DESC',
            ],
            // Properties enabled with empty request (default values)
            [
                [
                    'properties' => ['id' => 'asc', 'name' => 'desc'],
                ],
                [
                    'order' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o ORDER BY o.id ASC, o.name DESC',
            ],
        ];
    }
}
