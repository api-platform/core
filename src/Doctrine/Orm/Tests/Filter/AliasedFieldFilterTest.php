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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter as OrmAbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AliasedFieldFilterTest extends TestCase
{
    private function getFakeFilter(): object
    {
        return new class(managerRegistry: $this->createMock(ManagerRegistry::class), logger: $this->createMock(LoggerInterface::class), properties: ['name' => 'exact', 'some.relation.field' => 'partial'], propertyAliases: ['some.relation.field' => 'aliasedField']) extends OrmAbstractFilter {
            protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function getDescription(string $resourceClass): array
            {
                return [];
            }
        };
    }

    #[Group('filter-test')]
    public function testOrmFilterWithAliasedFieldsDenormalizes(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $denormalizePropertyNameClosure = function () {
            $that = $this;

            /* @var FilterInterface $that */
            /** @phpstan-ignore method.notFound */
            return $that->denormalizePropertyName('aliasedField');
        };

        $this->assertEquals('some.relation.field', $denormalizePropertyNameClosure->call($fakeFilter));

        $normalizePropertyNameClosure = function () {
            $that = $this;

            /* @var FilterInterface $that */
            /** @phpstan-ignore method.notFound */
            return $that->normalizePropertyName('some.relation.field');
        };

        $this->assertEquals('aliasedField', $normalizePropertyNameClosure->call($fakeFilter));
    }

    #[Group('filter-test')]
    public function testOrmFilterWithAliasedFieldsNormalizes(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $normalizePropertyNameClosure = function () {
            $that = $this;

            /* @var FilterInterface $that */
            /** @phpstan-ignore method.notFound */
            return $that->normalizePropertyName('some.relation.field');
        };

        $this->assertEquals('aliasedField', $normalizePropertyNameClosure->call($fakeFilter));
    }

    #[Group('filter-test')]
    public function testOrmFilterWithAliasedFieldsDefaultsOnUnaliasedProperty(): void
    {
        $fakeFilter = $this->getFakeFilter();

        $denormalizePropertyNameClosure = function () {
            $that = $this;

            /* @var FilterInterface $that */
            /** @phpstan-ignore method.notFound */
            return $that->denormalizePropertyName('name');
        };

        $normalizePropertyNameClosure = function () {
            $that = $this;

            /* @var FilterInterface $that */
            /** @phpstan-ignore method.notFound */
            return $that->normalizePropertyName('name');
        };

        $this->assertEquals('name', $denormalizePropertyNameClosure->call($fakeFilter));
        $this->assertEquals('name', $normalizePropertyNameClosure->call($fakeFilter));
    }
}
