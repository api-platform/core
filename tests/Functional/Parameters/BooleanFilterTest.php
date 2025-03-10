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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredBooleanParameter as FilteredBooleanParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredBooleanParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class BooleanFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredBooleanParameter::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredBooleanParameterDocument::class : FilteredBooleanParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[DataProvider('booleanFilterScenariosProvider')]
    public function testBooleanFilterResponses(string $url, int $expectedActiveItemCount, bool $expectedActiveStatus): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedActiveItemCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedActiveItemCount, $url));

        foreach ($filteredItems as $item) {
            $this->assertSame($expectedActiveStatus, $item['active'], \sprintf("Expected 'active' to be %s", $expectedActiveStatus));
        }
    }

    public static function booleanFilterScenariosProvider(): \Generator
    {
        yield 'active_true' => ['/filtered_boolean_parameters?active=true', 2, true];
        yield 'active_false' => ['/filtered_boolean_parameters?active=false', 1, false];
        yield 'active_numeric_1' => ['/filtered_boolean_parameters?active=1', 2, true];
        yield 'active_numeric_0' => ['/filtered_boolean_parameters?active=0', 1, false];
        yield 'enabled_alias_true' => ['/filtered_boolean_parameters?enabled=true', 2, true];
        yield 'enabled_alias_false' => ['/filtered_boolean_parameters?enabled=false', 1, false];
        yield 'enabled_alias_numeric_1' => ['/filtered_boolean_parameters?enabled=1', 2, true];
        yield 'enabled_alias_numeric_0' => ['/filtered_boolean_parameters?enabled=0', 1, false];
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('booleanFilterNullAndEmptyScenariosProvider')]
    public function testBooleanFilterWithNullAndEmptyValues(string $url): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $expectedItemCount = 3;
        $this->assertCount($expectedItemCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedItemCount, $url));
    }

    public static function booleanFilterNullAndEmptyScenariosProvider(): \Generator
    {
        yield 'active_null_value' => ['/filtered_boolean_parameters?active=null'];
        yield 'active_empty_value' => ['/filtered_boolean_parameters?active=', 3];
        yield 'enabled_alias_null_value' => ['/filtered_boolean_parameters?enabled=null'];
        yield 'enabled_alias_empty_value' => ['/filtered_boolean_parameters?enabled=', 3];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        $booleanStates = [true, true, false, null];
        foreach ($booleanStates as $activeValue) {
            $entity = new $entityClass(active: $activeValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
