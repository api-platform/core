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

namespace ApiPlatform\Doctrine\Odm\Tests\Filter;

use ApiPlatform\Doctrine\Odm\Filter\EndSearchFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\StartSearchFilter;
use ApiPlatform\Doctrine\Odm\Filter\WordStartSearchFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegexSearchFilterNestedArrayValuesTest extends TestCase
{
    private DocumentManager $manager;

    protected function setUp(): void
    {
        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
    }

    public static function provideFilters(): iterable
    {
        yield 'start' => [new StartSearchFilter()];
        yield 'end' => [new EndSearchFilter()];
        yield 'word start' => [new WordStartSearchFilter()];
    }

    #[DataProvider('provideFilters')]
    public function testNestedArrayValuesAreIgnored(FilterInterface $filter): void
    {
        $context = $this->apply($filter, [['foo' => 'bar']]);

        $this->assertArrayNotHasKey('match', $context);
    }

    #[DataProvider('provideFilters')]
    public function testNestedArrayValuesAreSkippedAmongScalarValues(FilterInterface $filter): void
    {
        $context = $this->apply($filter, [['foo' => 'bar'], 'baz']);

        $this->assertArrayHasKey('match', $context);
    }

    private function apply(FilterInterface $filter, array $values): array
    {
        $parameter = new QueryParameter(property: 'name', key: 'name');
        $parameter->setValue($values);
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
            'filters' => ['name' => $values],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        return $context;
    }
}
