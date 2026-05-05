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

namespace ApiPlatform\Doctrine\Odm\Tests;

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\ErrorHandler;

/**
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class DoctrineMongoDbOdmFilterTestCase extends KernelTestCase
{
    protected DocumentManager $manager;

    protected ManagerRegistry $managerRegistry;

    protected DocumentRepository $repository;

    protected string $resourceClass;

    protected string $filterClass;

    private bool $symfonyErrorHandlerWasRegistered = false;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine_mongodb');
        $this->repository = $this->manager->getRepository($this->resourceClass);
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
    public function testApply(?array $properties, array $filterParameters, array $expectedPipeline, ?callable $factory = null, ?string $resourceClass = null): void
    {
        $this->doTestApply($properties, $filterParameters, $expectedPipeline, $factory, $resourceClass);
    }

    protected function doTestApply(?array $properties, array $filterParameters, array $expectedPipeline, ?callable $filterFactory = null, ?string $resourceClass = null): void
    {
        $filterFactory ??= fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): FilterInterface => new ($this->filterClass)($managerRegistry, null, $properties);

        $repository = $this->repository;
        if ($resourceClass) {
            $repository = $this->manager->getRepository($resourceClass);
        }
        $resourceClass = $resourceClass ?: $this->resourceClass;
        $aggregationBuilder = $repository->createAggregationBuilder();
        $filterCallable = $filterFactory($this, $this->managerRegistry, $properties);
        $context = ['filters' => $filterParameters];
        $filterCallable->apply($aggregationBuilder, $resourceClass, null, $context);
        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException) {
        }

        $this->assertEquals($expectedPipeline, $pipeline);
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }

    abstract public static function provideApplyTestData(): array;
}
