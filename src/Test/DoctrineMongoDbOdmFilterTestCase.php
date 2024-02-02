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

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

    protected function setUp(): void
    {
        self::bootKernel();

        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine_mongodb');
        $this->repository = $this->manager->getRepository($this->resourceClass);
    }

    /**
     * @dataProvider provideApplyTestData
     */
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
