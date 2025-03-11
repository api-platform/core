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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredNumericParameter as FilteredNumericParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredNumericParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class NumericFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredNumericParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredNumericParameterDocument::class : FilteredNumericParameter::class;

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
        yield 'quantity_int_equal' => ['/filtered_numeric_parameters?quantity=10', 1];
        yield 'ratio_float_equal' => ['/filtered_numeric_parameters?ratio=1.0', 2];
        yield 'amount_alias_int_equal' => ['/filtered_numeric_parameters?amount=20', 2];
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
        yield 'quantity_int_null_value' => ['/filtered_numeric_parameters?quantity=null', 4];
        yield 'quantity_int_empty_value' => ['/filtered_numeric_parameters?quantity=', 4];
        yield 'ratio_float_null_value' => ['/filtered_numeric_parameters?ratio=null', 4];
        yield 'ratio_float_empty_value' => ['/filtered_numeric_parameters?ratio=', 4];
        yield 'amount_alias_int_null_value' => ['/filtered_numeric_parameters?amount=null', 4];
        yield 'amount_alias_int_empty_value' => ['/filtered_numeric_parameters?amount=', 4];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        foreach ([[10, 1.0], [20, 2.0], [30, 3.0], [20, 1.0]] as [$quantity, $ratio]) {
            $entity = new $entityClass(quantity: $quantity, ratio: $ratio);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
