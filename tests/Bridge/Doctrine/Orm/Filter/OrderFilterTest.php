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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
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
        $parameter = array_key_exists('parameter', $filterParameters) ? $filterParameters['parameter'] : 'order';
        $filter = new OrderFilter(
            $this->managerRegistry,
            $requestStack,
            $parameter,
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
        $filter = new OrderFilter($this->managerRegistry,
            new RequestStack(),
            'order',
            [
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
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new OrderFilter($this->managerRegistry,
            new RequestStack(),
            'order');
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
            'order[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
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
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.name ASC', Dummy::class),
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
                sprintf('SELECT o FROM %s o', Dummy::class),
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
                sprintf('SELECT o FROM %s o', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.name DESC', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name ASC', Dummy::class),
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
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 ORDER BY o.id ASC, o.name DESC, relatedDummy_a1.symfony DESC', Dummy::class),
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
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
            ],
        ];
    }
}
