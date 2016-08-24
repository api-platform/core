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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use phpmock\phpunit\PHPMock;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class SearchFilterTest extends KernelTestCase
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
     * @var IriConverterInterface
     */
    protected $iriConverter;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->iriConverter = self::$kernel->getContainer()->get('api_platform.iri_converter');
        $this->propertyAccessor = self::$kernel->getContainer()->get('property_accessor');
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
        $filter = new SearchFilter(
            $this->managerRegistry,
            $requestStack,
            $this->iriConverter,
            $this->propertyAccessor,
            $filterParameters['properties']
        );

        $filter->apply($queryBuilder, new QueryNameGenerator(), $this->resourceClass, 'op');
        $actual = strtolower($queryBuilder->getQuery()->getDQL());
        $expectedDql = strtolower($expected['dql']);

        $this->assertEquals(
            $expectedDql,
            $actual,
            sprintf('Expected `%s` for this `%s %s` request', $expectedDql, 'GET', $request->getUri())
        );

        if (!empty($expected['parameters'])) {
            foreach ($expected['parameters'] as $parameter => $expectedValue) {
                $actualValue = $queryBuilder->getQuery()->getParameter($parameter)->getValue();

                $this->assertEquals(
                    $expectedValue,
                    $actualValue,
                    sprintf('Expected `%s` for this `%s %s` request', var_export($expectedValue, true), 'GET', $request->getUri())
                );
            }
        }
    }

    public function testGetDescription()
    {
        $filter = new SearchFilter(
            $this->managerRegistry,
            new RequestStack(),
            $this->iriConverter,
            $this->propertyAccessor
        );
        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'integer',
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
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'decimal',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'decimal',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'json_array',
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
                'type' => 'boolean',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyBoolean[]' => [
                'property' => 'dummyBoolean',
                'type' => 'boolean',
                'required' => false,
                'strategy' => 'exact',
            ],
        ], $filter->getDescription($this->resourceClass));

        $filter = new SearchFilter(
            $this->managerRegistry,
            new RequestStack(),
            $this->iriConverter,
            $this->propertyAccessor,
            [
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
            ]
        );
        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
                'strategy' => 'exact',
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'integer',
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
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'json_array',
                'required' => false,
                'strategy' => 'exact',
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'json_array',
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
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummies.dummyDate[]' => [
                'property' => 'relatedDummies.dummyDate',
                'type' => 'datetime',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy' => [
                'property' => 'relatedDummy',
                'type' => 'iri',
                'required' => false,
                'strategy' => 'exact',
            ],
            'relatedDummy[]' => [
                'property' => 'relatedDummy',
                'type' => 'iri',
                'required' => false,
                'strategy' => 'exact',
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    /**
     * Providers 3 parameters:
     *  - filter parameters.
     *  - properties to test. Keys are the property name. If the value is true, the filter should work on the property,
     *    otherwise not.
     *  - expected DQL query and parameters value.
     *
     * @return array
     */
    public function filterProvider()
    {
        return [
            // Exact values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'name' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name = :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => 'exact',
                    ],
                ],
            ],
            // Exact case insensitive
            [
                [
                    'properties' => ['id' => null, 'name' => 'iexact'],
                ],
                [
                    'name' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) = LOWER(:p_1)', Dummy::class),
                    'parameters' => [
                        'p_1' => 'exact',
                    ],
                ],
            ],
            // invalid values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'foo' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o', Dummy::class),
                    'parameters' => [],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy' => null, 'relatedDummies' => null],
                ],
                [
                    'name' => ['foo'],
                    'relatedDummy' => ['foo'],
                    'relatedDummies' => [['foo']],
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o INNER JOIN o.relateddummy a_1 WHERE o.name = :p_1 AND a_1.id = :p_2', Dummy::class),
                    'parameters' => [
                        'p_2' => 'foo',
                    ],
                ],
            ],
            // partial values
            [
                [
                    'properties' => ['id' => null, 'name' => 'partial'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name like :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => '%partial%',
                    ],
                ],
            ],
            // partial case insensitive
            [
                [
                    'properties' => ['id' => null, 'name' => 'ipartial'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) like LOWER(:p_1)', Dummy::class),
                    'parameters' => [
                        'p_1' => '%partial%',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => 'start'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name like :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => 'partial%',
                    ],
                ],
            ],
            // start case insensitive
            [
                [
                    'properties' => ['id' => null, 'name' => 'istart'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) like LOWER(:p_1)', Dummy::class),
                    'parameters' => [
                        'p_1' => 'partial%',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => 'end'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name like :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => '%partial',
                    ],
                ],
            ],
            // end case insensitive
            [
                [
                    'properties' => ['id' => null, 'name' => 'iend'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) like LOWER(:p_1)', Dummy::class),
                    'parameters' => [
                        'p_1' => '%partial',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => 'word_start'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE o.name like :p_1_1 OR o.name like :p_1_2', Dummy::class),
                    'parameters' => [
                        'p_1_1' => 'partial%',
                        'p_1_2' => '% partial%',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => 'iword_start'],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o WHERE LOWER(o.name) like LOWER(:p_1_1) OR LOWER(o.name) like LOWER(:p_1_2)', Dummy::class),
                    'parameters' => [
                        'p_1_1' => 'partial%',
                        'p_1_2' => '% partial%',
                    ],
                ],
            ],
            // relations
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy' => null],
                ],
                [
                    'relatedDummy' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o inner join o.relatedDummy a_1 WHERE a_1.id = :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => 'exact',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy.id' => null],
                ],
                [
                    'relatedDummy.id' => '/related_dummies/1',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o inner join o.relatedDummy a_1 WHERE a_1.id = :p_1', Dummy::class),
                    'parameters' => [
                        'p_1' => 1,
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy' => null, 'relatedDummies' => null],
                ],
                [
                    'relatedDummy' => ['/related_dummies/1', '2'],
                    'relatedDummies' => '1',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o inner join o.relatedDummy a_1 inner join o.relatedDummies a_2 WHERE a_1.id IN (:p_1) AND a_2.id = :p_2', Dummy::class),
                    'parameters' => [
                        'p_1' => [1, 2],
                        'p_2' => 1,
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy.symfony' => null],
                ],
                [
                    'name' => 'exact',
                    'relatedDummy.symfony' => 'exact',
                ],
                [
                    'dql' => sprintf('SELECT o FROM %s o inner join o.relatedDummy a_1 WHERE o.name = :p_1 AND a_1.symfony = :p_2', Dummy::class),
                    'parameters' => [
                        'p_1' => 'exact',
                        'p_2' => 'exact',
                    ],
                ],
            ],
        ];
    }
}
