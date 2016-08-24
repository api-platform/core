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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
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
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class NumericFilterTest extends KernelTestCase
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
        $filter = new NumericFilter(
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
        $filter = new NumericFilter($this->managerRegistry,
            new RequestStack(), [
            'id' => null,
            'name' => null,
            'foo' => null,
            'dummyBoolean' => null,
        ]);
        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new NumericFilter($this->managerRegistry,
            new RequestStack());
        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'integer',
                'required' => false,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'decimal',
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
            // test with positive value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyPrice' => null],
                ],
                [
                    'dummyPrice' => 21,

                ],
                sprintf('SELECT o FROM %s o where o.dummyPrice = :dummyprice_dummyprice1', Dummy::class),
            ],
            // test with negative value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyPrice' => null],
                ],
                [
                    'dummyPrice' => -21,
                ],
                sprintf('SELECT o FROM %s o where o.dummyPrice = :dummyprice_dummyprice1', Dummy::class),
            ],
            // test with non-numeric value
            [
                [
                    'properties' => ['id' => null],
                ],
                [
                    'id' => 'toto',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            // test with 0 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyPrice' => null],
                ],
                [
                    'dummyPrice' => 0,
                ],
                sprintf('SELECT o FROM %s o where o.dummyPrice = :dummyprice_dummyprice1', Dummy::class),
            ],
            // test with nested properties.
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy.id' => null],
                ],
                [
                    'relatedDummy.id' => 0,
                ],
                sprintf('SELECT o FROM %s o left join o.relateddummy relateddummy_relateddummy1 where relateddummy_relateddummy1.id = :id_id1', Dummy::class),
            ],
            // test with one correct and one wrong value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyPrice' => null],
                ],
                [
                   'dummyPrice' => 10,
                   'name' => '15toto',
                ],
                sprintf('SELECT o FROM %s o where o.dummyPrice = :dummyprice_dummyprice1', Dummy::class),
            ],
            // test with numeric, non-numeric and inexisting field
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyPrice' => null],
                ],
                [
                    'toto' => 'toto',
                    'name' => 'gerard',
                    'dummyPrice' => '0',
                ],
                sprintf('SELECT o FROM %s o where o.dummyPrice = :dummyprice_dummyprice1', Dummy::class),
            ],
        ];
    }
}
