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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
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
class BooleanFilterTest extends KernelTestCase
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
        $filter = new BooleanFilter(
            $this->managerRegistry,
            $requestStack,
            $filterParameters['properties']
        );

        $uniqid = $this->getFunctionMock('ApiPlatform\Core\Bridge\Doctrine\Orm\Util', 'uniqid');
        $uniqid->expects($this->any())->willReturn('123456abcdefg');

        $filter->apply($queryBuilder, $this->resourceClass);
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
        $filter = new BooleanFilter($this->managerRegistry, new RequestStack(), [
            'id' => null,
            'name' => null,
            'foo' => null,
            'dummyBoolean' => null,
        ]);
        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'boolean',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new BooleanFilter($this->managerRegistry, new RequestStack());
        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'boolean',
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
            // test with true value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => 'true',

                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg', Dummy::class),
            ],
            // test with false value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => 'false',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg', Dummy::class),
            ],
            // test with non-boolean value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => 'toto',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            // test with 0 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => '0',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg', Dummy::class),
            ],
            // test with 1 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => '1',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg', Dummy::class),
            ],
            // test with nested properties.
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'relatedDummy.dummyBoolean' => null],
                ],
                [
                    'relatedDummy.dummyBoolean' => '1',
                ],
                sprintf('SELECT o FROM %s o left join o.relateddummy relateddummy_123456abcdefg where relateddummy_123456abcdefg.dummyboolean = :dummyboolean_123456abcdefg', Dummy::class),
            ],
            // test with multiple 1 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                   'dummyBoolean' => '1',
                   'name' => '1',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg and o.name = :name_123456abcdefg', Dummy::class),
            ],
            // test with multiple 0 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => '0',
                    'name' => '0',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg and o.name = :name_123456abcdefg', Dummy::class),
            ],
            // test with multiple true value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [

                    'dummyBoolean' => '1',
                    'name' => '1',

                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg and o.name = :name_123456abcdefg', Dummy::class),
            ],
            // test with multiple false value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => 'false',
                    'name' => 'false',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg and o.name = :name_123456abcdefg', Dummy::class),
            ],
            // test with both boolean, non-boolean and 0 value
            [
                [
                    'properties' => ['id' => null, 'name' => null, 'dummyBoolean' => null],
                ],
                [
                    'dummyBoolean' => 'false',
                    'toto' => 'toto',
                    'name' => 'true',
                    'id' => '0',
                ],
                sprintf('SELECT o FROM %s o where o.dummyBoolean = :dummyboolean_123456abcdefg and o.name = :name_123456abcdefg and o.id = :id_123456abcdefg', Dummy::class),
            ],
        ];
    }
}
