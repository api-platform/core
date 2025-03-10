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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredDateParameter as FilteredDateParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredDateParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class DateFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredDateParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredDateParameterDocument::class : FilteredDateParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[DataProvider('dateFilterScenariosProvider')]
    public function testDateFilterResponses(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function dateFilterScenariosProvider(): \Generator
    {
        yield 'created_at_after' => ['/filtered_date_parameters?createdAt[after]=2024-01-01', 3];
        yield 'created_at_before' => ['/filtered_date_parameters?createdAt[before]=2024-12-31', 3];
        yield 'created_at_before_single_result' => ['/filtered_date_parameters?createdAt[before]=2024-01-02', 1];
        yield 'created_at_strictly_after' => ['/filtered_date_parameters?createdAt[strictly_after]=2024-01-01', 2];
        yield 'created_at_strictly_before' => ['/filtered_date_parameters?createdAt[strictly_before]=2024-12-31T23:59:59Z', 3];
        yield 'date_alias_after' => ['/filtered_date_parameters?date[after]=2024-01-01', 3];
        yield 'date_alias_before' => ['/filtered_date_parameters?date[before]=2024-12-31', 3];
        yield 'date_alias_before_first' => ['/filtered_date_parameters?date[before]=2024-01-02', 1];
        yield 'date_alias_strictly_after' => ['/filtered_date_parameters?date[strictly_after]=2024-01-01', 2];
        yield 'date_alias_strictly_before' => ['/filtered_date_parameters?date[strictly_before]=2024-12-31T23:59:59Z', 3];
        yield 'date_alias_include_null_always_after_date' => ['/filtered_date_parameters?date_include_null_always[after]=2024-06-15', 3];
        yield 'date_alias_include_null_always_before_date' => ['/filtered_date_parameters?date_include_null_always[before]=2024-06-14', 2];
        yield 'date_alias_include_null_always_before_all_date' => ['/filtered_date_parameters?date_include_null_always[before]=2024-12-31', 4];
        yield 'date_alias_old_way' => ['/filtered_date_parameters?date_old_way[before]=2024-06-14', 2];
        yield 'date_alias_old_way_after_last_one' => ['/filtered_date_parameters?date_old_way[after]=2024-12-31', 1];
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('dateFilterNullAndEmptyScenariosProvider')]
    public function testDateFilterWithNullAndEmptyValues(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function dateFilterNullAndEmptyScenariosProvider(): \Generator
    {
        yield 'created_at_null_value' => ['/filtered_date_parameters?createdAt=null', 4];
        yield 'created_at_empty_value' => ['/filtered_date_parameters?createdAt=', 4];
        yield 'date_null_value_alias' => ['/filtered_date_parameters?date=null', 4];
        yield 'date_empty_value_alias' => ['/filtered_date_parameters?date=', 4];
        yield 'date_alias__include_null_always_with_null_alias' => ['/filtered_date_parameters?date_include_null_always=null', 4];
        yield 'date__alias_include_null_always_with_empty_alias' => ['/filtered_date_parameters?date_include_null_always=', 4];
        yield 'date_alias_old_way_with_null_alias' => ['/filtered_date_parameters?date_old_way=null', 4];
        yield 'date__alias_old_way_with_empty_alias' => ['/filtered_date_parameters?date_old_way=', 4];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-06-15'),
            new \DateTimeImmutable('2024-12-25'),
            null,
        ];

        foreach ($dates as $createdAtValue) {
            $entity = new $entityClass(createdAt: $createdAtValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
