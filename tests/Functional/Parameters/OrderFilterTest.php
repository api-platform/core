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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredOrderParameter as FilteredOrderParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredOrderParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class OrderFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredOrderParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredOrderParameterDocument::class : FilteredOrderParameter::class;

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
    #[DataProvider('orderFilterScenariosProvider')]
    public function testOrderFilterResponses(string $url, array $expectedOrder): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $orderedItems = $responseData['hydra:member'];

        $actualOrder = array_map(fn ($item) => $item['createdAt'] ?? null, $orderedItems);

        $this->assertSame($expectedOrder, $actualOrder, \sprintf('Expected order does not match for URL %s', $url));
    }

    public static function orderFilterScenariosProvider(): \Generator
    {
        yield 'created_at_ordered_asc' => [
            '/filtered_order_parameters?createdAt=asc',
            [null, '2024-01-01T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-12-25T00:00:00+00:00'],
        ];
        yield 'created_at_ordered_desc' => [
            '/filtered_order_parameters?createdAt=desc',
            ['2024-12-25T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-01-01T00:00:00+00:00', null],
        ];
        yield 'date_alias_ordered_asc' => [
            '/filtered_order_parameters?date=asc',
            [null, '2024-01-01T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-12-25T00:00:00+00:00'],
        ];
        yield 'date_alias_ordered_desc' => [
            '/filtered_order_parameters?date=desc',
            ['2024-12-25T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-01-01T00:00:00+00:00', null],
        ];
        yield 'date_null_always_first_alias_nulls_first' => [
            '/filtered_order_parameters?date_null_always_first=asc',
            [null, '2024-01-01T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-12-25T00:00:00+00:00'],
        ];
        yield 'date_null_always_first_alias_nulls_last' => [
            '/filtered_order_parameters?date_null_always_first=desc',
            ['2024-12-25T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-01-01T00:00:00+00:00', null],
        ];
        yield 'date_null_always_first_old_way_alias_nulls_first' => [
            '/filtered_order_parameters?date_null_always_first_old_way=asc',
            [null, '2024-01-01T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-12-25T00:00:00+00:00'],
        ];
        yield 'date_null_always_first_old_way_alias_nulls_last' => [
            '/filtered_order_parameters?date_null_always_first_old_way=desc',
            ['2024-12-25T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-01-01T00:00:00+00:00', null],
        ];
        yield 'order_property_created_at_nulls_first' => [
            '/filtered_order_parameters?order[createdAt]=asc',
            [null, '2024-01-01T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-12-25T00:00:00+00:00'],
        ];
        yield 'order_property_created_at_nulls_last' => [
            '/filtered_order_parameters?order[createdAt]=desc',
            ['2024-12-25T00:00:00+00:00', '2024-06-15T00:00:00+00:00', '2024-01-01T00:00:00+00:00', null],
        ];
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
            new \DateTimeImmutable('2024-12-25'),
            null,
            new \DateTimeImmutable('2024-06-15'),
        ];

        foreach ($dates as $createdAtValue) {
            $entity = new $entityClass(createdAt: $createdAtValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
