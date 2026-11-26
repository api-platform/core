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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedBookAction\BookAction;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedBookAction\BookActionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedBookAction\MergeBookAction;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DiscriminatorMapOpenApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [BookActionResource::class];
    }

    public function testDiscriminatedInputIsDocumentedAsOneOf(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $schemas = $json['components']['schemas'];

        $requestSchema = $json['paths']['/discriminated_book_actions']['post']['requestBody']['content']['application/ld+json']['schema'];
        $this->assertSame(['$ref' => '#/components/schemas/BookActionResource.BookAction'], $requestSchema);

        $this->assertSame([
            'oneOf' => [
                ['$ref' => '#/components/schemas/ArchiveBookAction'],
                ['$ref' => '#/components/schemas/MergeBookAction'],
            ],
            'discriminator' => [
                'propertyName' => 'action',
                'mapping' => [
                    'archive' => '#/components/schemas/ArchiveBookAction',
                    'merge' => '#/components/schemas/MergeBookAction',
                ],
            ],
        ], $schemas['BookActionResource.BookAction']);

        $archive = $schemas['ArchiveBookAction'];
        $this->assertSame(['type' => 'string', 'enum' => ['archive']], $archive['properties']['action']);
        $this->assertSame(['type' => 'string'], $archive['properties']['reason']);
        $this->assertSame(['action'], $archive['required']);

        $merge = $schemas['MergeBookAction'];
        $this->assertSame(['type' => 'string', 'enum' => ['merge']], $merge['properties']['action']);
        $this->assertArrayHasKey('sources', $merge['properties']);
        $this->assertSame(['action'], $merge['required']);
    }

    public function testDiscriminatorIsOpenApiOnly(): void
    {
        $schema = self::getContainer()->get('api_platform.json_schema.schema_factory')->buildSchema(BookAction::class, 'json', 'input');
        $definition = $schema->getDefinitions()['BookAction'];

        $this->assertSame([
            ['$ref' => '#/definitions/ArchiveBookAction'],
            ['$ref' => '#/definitions/MergeBookAction'],
        ], $definition['oneOf']);
        $this->assertArrayNotHasKey('discriminator', $definition);
    }

    public function testDiscriminatedInputIsDenormalized(): void
    {
        self::createClient()->request('POST', '/discriminated_book_actions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['action' => 'merge', 'target' => '/books/1', 'sources' => ['/books/2']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['performed' => MergeBookAction::class]);
    }
}
