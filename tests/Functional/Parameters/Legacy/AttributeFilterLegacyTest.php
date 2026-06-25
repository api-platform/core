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

namespace ApiPlatform\Tests\Functional\Parameters\Legacy;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Legacy\FilteredAttributeParameter as FilteredAttributeParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy\FilteredAttributeParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression coverage for the deprecated #[ApiFilter] attribute declaration of the surviving
 * Date/Range/Exists filters. The canonical QueryParameter form is covered by the
 * Date/Range/ExistsFilterTest classes. Remove together with the #[ApiFilter] attribute in 6.0.
 */
#[Group('legacy')]
final class AttributeFilterLegacyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredAttributeParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredAttributeParameterDocument::class : FilteredAttributeParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    #[DataProvider('attributeFilterScenariosProvider')]
    public function testAttributeFilterResponses(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();

        $this->assertCount($expectedCount, $responseData['hydra:member'], \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function attributeFilterScenariosProvider(): \Generator
    {
        // DateFilter via #[ApiFilter]
        yield 'date_after' => ['/legacy_filtered_attribute_parameters?createdAt[after]=2024-06-01', 2];
        yield 'date_before' => ['/legacy_filtered_attribute_parameters?createdAt[before]=2024-06-01', 1];
        // RangeFilter via #[ApiFilter]
        yield 'range_gt' => ['/legacy_filtered_attribute_parameters?quantity[gt]=15', 2];
        yield 'range_lt' => ['/legacy_filtered_attribute_parameters?quantity[lt]=15', 1];
        // ExistsFilter via #[ApiFilter]
        yield 'exists_true' => ['/legacy_filtered_attribute_parameters?exists[description]=true', 2];
        yield 'exists_false' => ['/legacy_filtered_attribute_parameters?exists[description]=false', 1];
    }

    /**
     * @throws \Throwable
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        $rows = [
            [new \DateTimeImmutable('2024-01-01'), 10, 'a'],
            [new \DateTimeImmutable('2024-06-15'), 20, null],
            [new \DateTimeImmutable('2024-12-25'), 30, 'c'],
        ];

        foreach ($rows as [$createdAt, $quantity, $description]) {
            $manager->persist(new $entityClass(createdAt: $createdAt, quantity: $quantity, description: $description));
        }

        $manager->flush();
    }
}
