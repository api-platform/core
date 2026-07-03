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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedFilterParameter as ConvertedFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedFilterParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that modern parameter filters (ExactFilter, SortFilter) work with
 * QueryParameter on a multi-word property when a nameConverter is configured.
 *
 * @see https://github.com/api-platform/core/issues/8380
 */
final class NameConverterModernFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ConvertedFilterParameter::class];
    }

    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? ConvertedFilterParameterDocument::class : ConvertedFilterParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    public function testExactFilterWithNameConverter(): void
    {
        $response = self::createClient()->request('GET', '/converted_filter_parameters?nameConverted=bar');
        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(1, $members);
        $this->assertSame('bar', $members[0]['name_converted']);
    }

    public function testSortFilterWithNameConverter(): void
    {
        $response = self::createClient()->request('GET', '/converted_filter_parameters?orderNameConverted=desc');
        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $names = array_map(static fn (array $m): string => $m['name_converted'], $members);
        $this->assertSame(['foo', 'baz', 'bar'], $names);
    }

    /**
     * @param class-string $entityClass
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        foreach (['bar', 'baz', 'foo'] as $name) {
            $entity = new $entityClass();
            $entity->nameConverted = $name;
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
