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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
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
class OrderFilterTest extends KernelTestCase
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
    public function testApply(string $orderParameterName, $properties, array $filterParameters, string $expected)
    {
        $request = Request::create('/api/dummies', 'GET', $filterParameters);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new OrderFilter(
            $this->managerRegistry,
            $requestStack,
            $orderParameterName,
            null,
            $properties
        );

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass);
        $actual = $queryBuilder->getQuery()->getDQL();

        $this->assertEquals($expected, $actual);
    }

    public function testGetDescription()
    {
        $filter = new OrderFilter(
            $this->managerRegistry,
            new RequestStack(),
            'order',
            null,
            [
                'id' => null,
                'name' => null,
                'foo' => null,
            ]
        );

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
        $filter = new OrderFilter(
            $this->managerRegistry,
            new RequestStack(),
            'order'
        );

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
            'order[dummyFloat]' => [
                'property' => 'dummyFloat',
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
     * Provides test data.
     *
     * Provides 4 parameters:
     *  - order parameter name
     *  - configuration of filterable properties
     *  - filter parameters
     *  - expected DQL query
     *
     * @return array
     */
    public function provideApplyTestData(): array
    {
        return [
            'valid values' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
            ],
            'invalid values' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'invalid',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
            ],
            'valid values (properties not enabled)' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'alias' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
            ],
            'invalid values (properties not enabled)' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
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
            'invalid property (property not enabled)' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'invalid property (property enabled)' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                    'unknown' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'custom order parameter name' => [
                'customOrder',
                [
                    'id' => null,
                    'name' => null,
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
            'valid values (all properties enabled)' => [
                'order',
                null,
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name ASC', Dummy::class),
            ],
            'nested property' => [
                'order',
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.symfony' => null,
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
            'empty values with default sort direction' => [
                'order',
                [
                    'id' => 'asc',
                    'name' => 'desc',
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
