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
        $resource = $this->isMongoDB() ? FilteredBooleanParameterDocument::class : FilteredBooleanParameter::class;

        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[DataProvider('booleanFilterTrueFalseValuesProvider')]
    public function testBooleanFilter(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $entities = $data['hydra:member'];

        $this->assertCount($expectedCount, $entities, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $expectedValue = str_contains($url, '=true') || str_contains($url, '=1');
        foreach ($entities as $entity) {
            $errorMessage = \sprintf("Expected 'active' to be %s", $expectedValue ? 'true' : 'false');
            $this->assertSame($expectedValue, $entity['active'], $errorMessage);
        }
    }

    public static function booleanFilterTrueFalseValuesProvider(): \Generator
    {
        yield 'active_true' => ['/filtered_boolean_parameters?active=true', 2];
        yield 'active_false' => ['/filtered_boolean_parameters?active=false', 1];
        yield 'active_1' => ['/filtered_boolean_parameters?active=1', 2];
        yield 'active_0' => ['/filtered_boolean_parameters?active=0', 1];
        yield 'enabled_true' => ['/filtered_boolean_parameters?enabled=true', 2];
        yield 'enabled_false' => ['/filtered_boolean_parameters?enabled=false', 1];
        yield 'enabled_1' => ['/filtered_boolean_parameters?enabled=1', 2];
        yield 'enabled_0' => ['/filtered_boolean_parameters?enabled=0', 1];
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('nullableAndEmptyBooleanFilterProvider')]
    public function testBooleanFilterWithNullAndEmptyValues(string $url): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $entities = $data['hydra:member'];

        $expectedCount = 3;
        $this->assertCount($expectedCount, $entities, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function nullableAndEmptyBooleanFilterProvider(): \Generator
    {
        yield 'null_value' => ['/filtered_boolean_parameters?active=null'];
        yield 'null_value_alias' => ['/filtered_boolean_parameters?enabled=null'];
        yield 'active_empty' => ['/filtered_boolean_parameters?active=', 3];
        yield 'enabled_empty' => ['/filtered_boolean_parameters?enabled=', 3];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $resource): void
    {
        $manager = $this->getManager();

        $entitiesData = [true, true, false, null];
        foreach ($entitiesData as $activeValue) {
            $entity = new $resource(active: $activeValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
