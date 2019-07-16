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

namespace ApiPlatform\Core\Test;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class DoctrineMongoDbOdmFilterTestCase extends KernelTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var DocumentRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $resourceClass = Dummy::class;

    /**
     * @var string
     */
    protected $filterClass;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = TestCase::createTestDocumentManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine_mongodb');
        $this->repository = $manager->getRepository(Dummy::class);
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply(?array $properties, array $filterParameters, array $expectedPipeline, callable $factory = null)
    {
        $this->doTestApply($properties, $filterParameters, $expectedPipeline, $factory);
    }

    protected function doTestApply(?array $properties, array $filterParameters, array $expectedPipeline, callable $filterFactory = null)
    {
        if (null === $filterFactory) {
            $filterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): FilterInterface {
                $filterClass = $this->filterClass;

                return new $filterClass($managerRegistry, null, $properties);
            };
        }

        $aggregationBuilder = $this->repository->createAggregationBuilder();
        $filterCallable = $filterFactory($this->managerRegistry, $properties);
        $context = ['filters' => $filterParameters];
        $filterCallable->apply($aggregationBuilder, $this->resourceClass, null, $context);
        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException $e) {
        }

        $this->assertEquals($expectedPipeline, $pipeline);
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }

    abstract public function provideApplyTestData(): array;
}
