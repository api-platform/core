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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class NumericFilterTest extends KernelTestCase
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

        $filter = new NumericFilter(
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
        $filter = new NumericFilter(
            $this->managerRegistry,
            new RequestStack(),
            null,
            [
                'id' => null,
                'name' => null,
                'foo' => null,
                'dummyBoolean' => null,
            ]
        );

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new NumericFilter(
            $this->managerRegistry,
            new RequestStack()
        );

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'string',
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
            'numeric string (positive integer)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => '21',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
            ],
            'numeric string (negative integer)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => '-21',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
            ],
            'non-numeric' => [
                [
                    'id' => null,
                ],
                [
                    'id' => 'toto',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'numeric string ("0")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => 0,
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.id' => null,
                ],
                [
                    'relatedDummy.id' => 0,
                ],
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', Dummy::class),
            ],
            'mixed numeric and non-numeric' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                   'dummyPrice' => 10,
                   'name' => '15toto',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
            ],
            'mixed numeric, non-numeric and invalid property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'toto' => 'toto',
                    'name' => 'gerard',
                    'dummyPrice' => '0',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
            ],
        ];
    }
}
