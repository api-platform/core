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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PotentialAction\ActionableBookNote;
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
        return [ActionableBook::class, ActionableBookNote::class];
    }

    public function testItemAdvertisesItemLevelActions(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $actions = $response->toArray()['potentialAction'];
        $this->assertSame(['LikeAction', 'ShareAction', 'ArchiveAction', 'UpdateAction', 'DeleteAction'], array_column($actions, '@type'));

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

    public function testItemAdvertisesPatchWithoutInput(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $action = $this->findAction($response->toArray()['potentialAction'], 'UpdateAction');
        $this->assertSame('PATCH', $action['target']['httpMethod']);
        $this->assertSame('/actionable_books/1', $action['target']['urlTemplate']);
        $this->assertArrayNotHasKey('object', $action);
        $this->assertSame([], $this->getInputKeys($action));
    }

    public function testItemAdvertisesDeleteWithoutInput(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $action = $this->findAction($response->toArray()['potentialAction'], 'DeleteAction');
        $this->assertSame('DELETE', $action['target']['httpMethod']);
        $this->assertSame('/actionable_books/1', $action['target']['urlTemplate']);
        $this->assertArrayNotHasKey('encodingType', $action['target']);
        $this->assertSame([], $this->getInputKeys($action));
    }

    public function testItemDoesNotAdvertiseDeniedAction(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $urlTemplates = array_column(array_column($response->toArray()['potentialAction'], 'target'), 'urlTemplate');
        $this->assertNotContains('/actionable_books/1/lock', $urlTemplates);
    }

    public function testCollectionAdvertisesCollectionLevelActions(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertCount(1, $json['potentialAction']);
        $this->assertSame('CreateAction', $json['potentialAction'][0]['@type']);
        $this->assertSame('/actionable_books', $json['potentialAction'][0]['target']['urlTemplate']);
        $this->assertSame('POST', $json['potentialAction'][0]['target']['httpMethod']);
        $this->assertSame('required', $json['potentialAction'][0]['result']['title-input']);
        $this->assertArrayNotHasKey('title-input', $json['potentialAction'][0]);

        $this->assertArrayNotHasKey('potentialAction', $json['hydra:member'][0]);
    }

    public function testSubresourceCollectionAdvertisesCollectionLevelActions(): void
    {
        $response = self::createClient()->request('GET', '/actionable_books/1/notes', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertCount(1, $json['potentialAction']);
        $this->assertSame('CreateAction', $json['potentialAction'][0]['@type']);
        $this->assertSame('/actionable_books/1/notes', $json['potentialAction'][0]['target']['urlTemplate']);
        $this->assertSame('required', $json['potentialAction'][0]['result']['body-input']);
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

    /**
     * @param list<array<string, mixed>> $actions
     *
     * @return array<string, mixed>
     */
    private function findAction(array $actions, string $type): array
    {
        foreach ($actions as $action) {
            if ($type === $action['@type']) {
                return $action;
            }
        }

        $this->fail(\sprintf('No "%s" action found.', $type));
    }

    /**
     * @param array<string, mixed> $action
     *
     * @return list<string>
     */
    private function getInputKeys(array $action): array
    {
        $keys = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($action)) as $key => $value) {
            if (\is_string($key) && str_ends_with($key, '-input')) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
