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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\Chair;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\Fork;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\Spoon;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\Table;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class NotExposedTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Chair::class, Table::class, Fork::class, Spoon::class];
    }

    public function testChairsCollectionIsExposedWithGenIdIris(): void
    {
        $response = self::createClient()->request('GET', '/chairs', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertSame('/contexts/Chair', $data['@context']);
        $this->assertSame('/chairs', $data['@id']);
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertCount(2, $data['hydra:member']);
        foreach ($data['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/.well-known/genid/.+$#', $member['@id']);
            $this->assertSame('Chair', $member['@type']);
        }
    }

    public function testTablesCollectionExposesItemIris(): void
    {
        $response = self::createClient()->request('GET', '/tables', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('/contexts/Table', $data['@context']);
        $this->assertSame(2, $data['hydra:totalItems']);
        foreach ($data['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/tables/.+$#', $member['@id']);
            $this->assertSame('Table', $member['@type']);
        }
    }

    public static function forkUris(): iterable
    {
        yield ['/forks'];
        yield ['/fourchettes'];
    }

    #[DataProvider('forkUris')]
    public function testForkMultipleCollectionsExposed(string $uri): void
    {
        $response = self::createClient()->request('GET', $uri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('/contexts/Fork', $data['@context']);
        $this->assertSame(2, $data['hydra:totalItems']);
        foreach ($data['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/forks/.+$#', $member['@id']);
            $this->assertSame('Fork', $member['@type']);
        }
    }

    public static function spoonUris(): iterable
    {
        yield ['/spoons'];
        yield ['/cuillers'];
    }

    #[DataProvider('spoonUris')]
    public function testSpoonCollectionExposesCuillersAsItemIris(string $uri): void
    {
        $response = self::createClient()->request('GET', $uri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame('/contexts/Spoon', $data['@context']);
        $this->assertSame(2, $data['hydra:totalItems']);
        foreach ($data['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/cuillers/.+$#', $member['@id']);
            $this->assertSame('Spoon', $member['@type']);
        }
    }

    public static function notExposedItemUris(): iterable
    {
        yield ['/tables/12345'];
        yield ['/forks/12345'];
    }

    #[DataProvider('notExposedItemUris')]
    public function testNotExposedItemReturns404(string $uri): void
    {
        self::createClient()->request('GET', $uri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'This route does not aim to be called.']);
    }

    public function testGenidNotExposedReturns404WithExplanation(): void
    {
        self::createClient()->request('GET', '/.well-known/genid/12345', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'detail' => 'This route is not exposed on purpose. It generates an IRI for a collection resource without identifier nor item operation.',
        ]);
    }

    public function testSpoonItemViaCuillersIsExposed(): void
    {
        self::createClient()->request('GET', '/cuillers/12345', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Spoon',
            '@id' => '/cuillers/12345',
            '@type' => 'Spoon',
            'id' => '12345',
            'owner' => 'Vincent',
        ]);
    }
}
