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

namespace ApiPlatform\Doctrine\Orm\Tests;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\ErrorHandler;

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

    private bool $symfonyErrorHandlerWasRegistered = false;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class);
    }

    /**
     * Symfony\Bundle\FrameworkBundle\FrameworkBundle::boot() registers Symfony's ErrorHandler via
     * set_exception_handler() but never unregisters it: each kernel boot leaks one entry on the
     * exception handler stack, which PHPUnit flags as Risky. Track whether the handler was already
     * present before the test so we only pop the entry our own test introduced.
     */
    #[Before]
    protected function captureExceptionHandlerStack(): void
    {
        $this->symfonyErrorHandlerWasRegistered = self::isSymfonyErrorHandlerRegistered();
    }

    #[After]
    protected function restoreExceptionHandlerStack(): void
    {
        if (!$this->symfonyErrorHandlerWasRegistered && self::isSymfonyErrorHandlerRegistered()) {
            restore_exception_handler();
        }
    }

    private static function isSymfonyErrorHandlerRegistered(): bool
    {
        $current = set_exception_handler(static fn () => null);
        restore_exception_handler();

        return \is_array($current) && $current[0] instanceof ErrorHandler;
    }

    #[DataProvider('provideApplyTestData')]
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

            $this->assertNotNull($queryParameter, \sprintf('Expected query parameter "%s" to be set', $parameterName));
            $this->assertEquals($expectedParameterValue, $queryParameter->getValue(), \sprintf('Expected query parameter "%s" to be "%s"', $parameterName, var_export($expectedParameterValue, true)));
        }
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }

    abstract public static function provideApplyTestData(): array;
}
