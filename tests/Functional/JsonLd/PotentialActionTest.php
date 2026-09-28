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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PotentialAction\ActionableBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PotentialActionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ActionableBook::class];
    }

    public function testItemAdvertisesItemLevelActions(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $actions = $response->toArray()['potentialAction'];
        $this->assertSame(['LikeAction', 'ShareAction', 'schema:ArchiveAction'], array_column($actions, '@type'));

        $this->assertSame('EntryPoint', $actions[0]['target']['@type']);
        $this->assertSame('/actionable_books/1/reviews', $actions[0]['target']['urlTemplate']);
        $this->assertSame('POST', $actions[0]['target']['httpMethod']);
        $this->assertContains('application/ld+json', (array) $actions[0]['target']['encodingType']);
        $this->assertContains('application/ld+json', (array) $actions[0]['target']['contentType']);
        $this->assertArrayNotHasKey('recipient-input', $actions[0]);

        $this->assertSame('/actionable_books/1/reviews', $actions[1]['target']['urlTemplate']);
        $this->assertSame('required', $actions[1]['recipient-input']);

        $this->assertSame('/actionable_books/1/archive', $actions[2]['target']['urlTemplate']);
        $this->assertSame('required', $actions[2]['reason-input']);
    }

    public function testCollectionAdvertisesCollectionLevelActions(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertCount(1, $json['potentialAction']);
        $this->assertSame('schema:CreateAction', $json['potentialAction'][0]['@type']);
        $this->assertSame('/actionable_books', $json['potentialAction'][0]['target']['urlTemplate']);
        $this->assertSame('POST', $json['potentialAction'][0]['target']['httpMethod']);
        $this->assertSame('required', $json['potentialAction'][0]['title-input']);

        $this->assertArrayNotHasKey('potentialAction', $json['hydra:member'][0]);
    }

    public function testContextScopesActionsToSchemaOrg(): void
    {
        $response = self::createClient()->request('GET', '/contexts/ActionableBook', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $this->assertSame([
            '@id' => 'https://schema.org/potentialAction',
            '@context' => ['@vocab' => 'https://schema.org/', 'schema' => 'https://schema.org/'],
        ], $response->toArray()['@context']['potentialAction']);
    }
}
