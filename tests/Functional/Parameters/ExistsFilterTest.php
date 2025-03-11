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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FilteredExistsParameter as FilteredExistsParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilteredExistsParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ExistsFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredExistsParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredExistsParameterDocument::class : FilteredExistsParameter::class;

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
    #[DataProvider('existsFilterScenariosProvider')]
    public function testExistsFilterResponses(string $url, int $expectedCount): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));
    }

    public static function existsFilterScenariosProvider(): \Generator
    {
        yield 'created_at_exists_entities' => ['/filtered_exists_parameters?createdAt=true', 2];
        yield 'created_at_not_exists' => ['/filtered_exists_parameters?createdAt=false', 1];
        yield 'has_creation_date_alias_exists_entities' => ['/filtered_exists_parameters?hasCreationDate=true', 2];
        yield 'has_creation_date_alias__not_exists_entities' => ['/filtered_exists_parameters?hasCreationDate=false', 1];
        yield 'exists_property_created_at_entities' => ['/filtered_exists_parameters?exists[createdAt]=true', 2];
        yield 'exists_property_created_at_not_exists' => ['/filtered_exists_parameters?exists[createdAt]=false', 1];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        foreach ([new \DateTimeImmutable(), null, new \DateTimeImmutable()] as $createdAt) {
            $entity = new $entityClass(createdAt: $createdAt);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
