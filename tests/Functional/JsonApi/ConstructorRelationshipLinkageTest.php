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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8702\Issue8702Author;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8702\Issue8702Book;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ConstructorRelationshipLinkageTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Issue8702Author::class, Issue8702Book::class];
    }

    public function testToOneResourceLinkage(): void
    {
        $response = $this->post(['author' => ['data' => ['type' => 'Issue8702Author', 'id' => '/issue8702_authors/1']]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('/issue8702_authors/1', $response->toArray()['data']['relationships']['author']['data']['id']);
    }

    public function testToOneNullLinkage(): void
    {
        $response = $this->post(['author' => ['data' => null]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertNull($response->toArray()['data']['relationships']['author']['data']);
    }

    public function testToManyResourceLinkage(): void
    {
        $response = $this->post(['coAuthors' => ['data' => [['type' => 'Issue8702Author', 'id' => '/issue8702_authors/1']]]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('/issue8702_authors/1', $response->toArray()['data']['relationships']['coAuthors']['data'][0]['id']);
    }

    public function testAttributeWithDataKeyIsNotUnwrapped(): void
    {
        $response = $this->post([], ['payload' => ['data' => [1, 2], 'x' => 3]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(['data' => [1, 2], 'x' => 3], $response->toArray()['data']['attributes']['payload']);
    }

    private function post(array $relationships, array $attributes = []): ResponseInterface
    {
        return self::createClient()->request('POST', '/issue8702_books', [
            'headers' => ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'Issue8702Book', 'attributes' => ['title' => 'A'] + $attributes, 'relationships' => $relationships]],
        ]);
    }
}
