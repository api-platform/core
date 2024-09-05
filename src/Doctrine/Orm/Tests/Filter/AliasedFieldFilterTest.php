<?php

namespace App\Tests;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter as OrmAbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class FilterTest extends ApiTestCase
{
    private function getFakeFilter(): object
    {
        return new class(
            managerRegistry: $this->createMock(ManagerRegistry::class),
            logger: $this->createMock(LoggerInterface::class),
            properties: ['name' => 'exact', 'some.relation.field' => 'partial'],
            propertyAliases: ['some.relation.field' => 'aliasedField'],
        ) extends OrmAbstractFilter {
            protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function getDescription(string $resourceClass): array
            {
                return [];
            }
        };
    }

    /**
     * @group filter-test
     */
    public function testOrmFilterWithAliasedFieldsDenormalizes(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $denormalizePropertyNameClosure = function() {
            /** @var FilterInterface $this */
            return $this->denormalizePropertyName('aliasedField');
        };

        $this->assertEquals('some.relation.field', $denormalizePropertyNameClosure->call($fakeFilter));

        $normalizePropertyNameClosure = function() {
            /** @var FilterInterface $this */
            return $this->normalizePropertyName('some.relation.field');
        };

        $this->assertEquals('aliasedField', $normalizePropertyNameClosure->call($fakeFilter));
    }

    /**
     * @group filter-test
     */
    public function testOrmFilterWithAliasedFieldsNormalizes(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $normalizePropertyNameClosure = function() {
            /** @var FilterInterface $this */
            return $this->normalizePropertyName('some.relation.field');
        };

        $this->assertEquals('aliasedField', $normalizePropertyNameClosure->call($fakeFilter));
    }

    /**
     * @group filter-test
     */
    public function testOrmFilterWithAliasedFieldsDefaultsOnUnaliasedProperty(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $normalizePropertyNameClosure = function() {
            /** @var FilterInterface $this */
            return $this->normalizePropertyName('name');
        };

        $normalizePropertyNameClosure = function() {
            /** @var FilterInterface $this */
            return $this->normalizePropertyName('name');
        };

        $this->assertEquals('name', $normalizePropertyNameClosure->call($fakeFilter));
        $this->assertEquals('name', $normalizePropertyNameClosure->call($fakeFilter));
    }
}
