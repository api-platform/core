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

namespace ApiPlatform\Test;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class DoctrineOrmFilterTestCase extends KernelTestCase
{
    protected ManagerRegistry $managerRegistry;

    protected EntityRepository $repository;

    protected string $resourceClass = Dummy::class;

    protected const ALIAS = 'o';

    protected string $filterClass;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class);
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply(?array $properties, array $filterParameters, string $expectedDql, ?array $expectedParameters = null, ?callable $factory = null, ?string $resourceClass = null): void
    {
        $this->doTestApply($properties, $filterParameters, $expectedDql, $expectedParameters, $factory, $resourceClass);
    }

    protected function doTestApply(?array $properties, array $filterParameters, string $expectedDql, ?array $expectedParameters = null, ?callable $filterFactory = null, ?string $resourceClass = null): void
    {
        if (null === $filterFactory) {
            $filterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): FilterInterface => new ($this->filterClass)($managerRegistry, null, $properties);
        }

        $repository = $this->repository;
        if ($resourceClass) {
            /** @var EntityRepository $repository */
            $repository = $this->managerRegistry->getManagerForClass($resourceClass)->getRepository($resourceClass);
        }
        $resourceClass = $resourceClass ?: $this->resourceClass;
        $queryBuilder = $repository->createQueryBuilder(static::ALIAS);
        $filterCallable = $filterFactory($this, $this->managerRegistry, $properties);
        $filterCallable->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, ['filters' => $filterParameters]);

        $this->assertSame($expectedDql, $queryBuilder->getQuery()->getDQL());

        if (null === $expectedParameters) {
            return;
        }

        foreach ($expectedParameters as $parameterName => $expectedParameterValue) {
            $queryParameter = $queryBuilder->getQuery()->getParameter($parameterName);

            $this->assertNotNull($queryParameter, sprintf('Expected query parameter "%s" to be set', $parameterName));
            $this->assertEquals($expectedParameterValue, $queryParameter->getValue(), sprintf('Expected query parameter "%s" to be "%s"', $parameterName, var_export($expectedParameterValue, true)));
        }
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }

    abstract public static function provideApplyTestData(): array;
}
