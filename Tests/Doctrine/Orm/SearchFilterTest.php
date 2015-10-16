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
use Doctrine\ORM\EntityRepository;
use Dunglas\ApiBundle\Api\Resource;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class SearchFilterTest extends KernelTestCase
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
        $class = 'Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy';
        $manager = DoctrineTestHelper::createTestEntityManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->iriConverter = self::$kernel->getContainer()->get('api.iri_converter');
        $this->propertyAccessor = self::$kernel->getContainer()->get('api.property_accessor');
        $this->repository = $manager->getRepository($class);
        $this->resource = new Resource($class);
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
        $filter = new SearchFilter(
            $this->managerRegistry,
            $requestStack,
            $this->iriConverter,
            $this->propertyAccessor,
            $filterParameters['properties']
        );

        $filter->apply($this->resource, $queryBuilder);
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
                    sprintf('Expected `%s` for this `%s %s` request', $expectedValue, 'GET', $request->getUri())
                );
            }
        }
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
                    'dql' => 'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.name like :name',
                    'parameters' => [
                        'name' => 'exact',
                    ],
                ],
            ],
            // partial values
            [
                [
                    'properties' => ['id' => null, 'name' => null],
                ],
                [
                    'name' => 'partial',
                ],
                [
                    'dql' => 'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o WHERE o.name like :name',
                    'parameters' => [
                        'name' => 'partial',
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
                    'dql' => 'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o inner join o.relateddummy api_relateddummy WHERE api_relateddummy.id = :relateddummy',
                    'parameters' => [
                        'relatedDummy' => 'exact',
                    ],
                ],
            ],
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummies' => null],
                ],
                [
                    'relatedDummies' => 'exact',
                ],
                [
                    'dql' => 'SELECT o FROM Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy o inner join o.relateddummies api_relateddummies WHERE api_relateddummies.id IN (:relateddummies)',
                ],
            ],
        ];
    }
}
