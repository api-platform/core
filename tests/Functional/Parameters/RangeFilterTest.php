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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredRangeParameter as FilteredRangeParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredRangeParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class RangeFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredRangeParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredRangeParameterDocument::class : FilteredRangeParameter::class;

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
    #[DataProvider('rangeFilterScenariosProvider')]
    public function testRangeFilterResponses(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function rangeFilterScenariosProvider(): \Generator
    {
        yield 'quantity_greater_than' => ['/filtered_range_parameters?quantity[gt]=10', 3];
        yield 'quantity_less_than' => ['/filtered_range_parameters?quantity[lt]=50', 3];
        yield 'quantity_between' => ['/filtered_range_parameters?quantity[between]=15..40', 2];
        yield 'quantity_gte' => ['/filtered_range_parameters?quantity[gte]=20', 3];
        yield 'quantity_lte' => ['/filtered_range_parameters?quantity[lte]=30', 3];
        yield 'quantity_greater_than_and_less_than' => ['/filtered_range_parameters?quantity[gt]=10&quantity[lt]=50', 2];
        yield 'quantity_gte_and_lte' => ['/filtered_range_parameters?quantity[gte]=20&quantity[lte]=30', 2];
        yield 'quantity_gte_and_less_than' => ['/filtered_range_parameters?quantity[gte]=15&quantity[lt]=50', 2];
        yield 'quantity_between_and_lte' => ['/filtered_range_parameters?quantity[between]=15..40&quantity[lte]=30', 2];
        yield 'amount_alias_greater_than' => ['/filtered_range_parameters?amount[gt]=10', 3];
        yield 'amount_alias_less_than' => ['/filtered_range_parameters?amount[lt]=50', 3];
        yield 'amount_alias_between' => ['/filtered_range_parameters?amount[between]=15..40', 2];
        yield 'amount_alias_gte' => ['/filtered_range_parameters?amount[gte]=20', 3];
        yield 'amount_alias_lte' => ['/filtered_range_parameters?amount[lte]=30', 3];
        yield 'amount_alias_gte_and_lte' => ['/filtered_range_parameters?amount[gte]=20&amount[lte]=30', 2];
        yield 'amount_alias_greater_than_and_less_than' => ['/filtered_range_parameters?amount[gt]=10&amount[lt]=50', 2];
        yield 'amount_alias_between_and_gte' => ['/filtered_range_parameters?amount[between]=15..40&amount[gte]=20', 2];
        yield 'amount_alias_lte_and_between' => ['/filtered_range_parameters?amount[lte]=30&amount[between]=15..40', 2];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[DataProvider('nullAndEmptyScenariosProvider')]
    public function testRangeFilterWithNullAndEmptyValues(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function nullAndEmptyScenariosProvider(): \Generator
    {
        yield 'quantity_null_value' => ['/filtered_range_parameters?quantity=null', 4];
        yield 'quantity_empty_value' => ['/filtered_range_parameters?quantity=', 4];
        yield 'amont_alias_null_value' => ['/filtered_range_parameters?amount=null', 4];
        yield 'amount_alias_empty_value' => ['/filtered_range_parameters?amount=', 4];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        foreach ([10, 20, 30, 50] as $quantity) {
            $entity = new $entityClass(quantity: $quantity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
