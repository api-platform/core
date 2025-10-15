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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Recipe;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class McpTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private int $jsonRpcId = 1;
    private array $mcpHeaders;

    public static function getResources(): array
    {
        return [Recipe::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mcpHeaders = [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ];

        $this->recreateSchema([Recipe::class]);

        $manager = $this->getManager();
        $recipe = new Recipe();
        $recipe->name = 'French Onion Soup';
        $recipe->description = 'A classic French soup.';
        $recipe->cookTime = 'PT1H';
        $recipe->prepTime = 'PT20M';
        $manager->persist($recipe);
        $manager->flush();
    }

    public function testMcpEndpoint(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        $client = self::createClient();

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('initialize', [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => [
                    'name' => 'ApiPlatform Test Suite',
                    'version' => '1.0',
                ],
                'capabilities' => new \stdClass(),
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('mcp-session-id');
        $this->mcpHeaders['mcp-session-id'] = $response->getHeaders()['mcp-session-id'][0];

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/list'),
        ]);
        $this->assertResponseIsSuccessful();

        $baseSchema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'cookTime' => ['type' => ['string', 'null']],
                'prepTime' => ['type' => ['string', 'null']],
            ],
        ];

        $this->assertJsonContains([
            'result' => [
                'tools' => [
                    ['name' => 'recipe_create', 'inputSchema' => $baseSchema],
                    ['name' => 'recipe_upsert_by_id', 'inputSchema' => $baseSchema + ['properties' => ['id' => ['type' => 'integer']] + $baseSchema['properties']]],
                    ['name' => 'recipe_update_by_id', 'inputSchema' => $baseSchema + ['properties' => ['id' => ['type' => 'integer']] + $baseSchema['properties']]],
                    ['name' => 'recipe_delete_by_id', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']], 'required' => ['id']]],
                ],
            ],
        ]);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/list'),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'result' => [
                'resources' => [
                    ['uri' => 'http://localhost/recipes', 'name' => 'recipe_retrieve_list', 'mimeType' => 'application/ld+json'],
                    ['uri' => 'http://localhost/docs.jsonopenapi', 'name' => 'openapi_spec', 'description' => 'The OpenAPI specification for this API.', 'mimeType' => 'application/vnd.openapi+json'],
                    ['uri' => 'http://localhost/docs.jsonld', 'name' => 'hydra_docs', 'description' => 'The Hydra documentation for this API.', 'mimeType' => 'application/ld+json'],
                    ['uri' => 'http://localhost/entrypoint', 'name' => 'api_entrypoint', 'description' => 'The main entrypoint for the API.', 'mimeType' => 'application/ld+json'],
                ],
            ],
        ]);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/templates/list'),
        ]);

        $this->assertJsonContains([
            'result' => [
                'resourceTemplates' => [
                    [
                        'uriTemplate' => 'http://localhost/recipes/{id}',
                        'name' => 'recipe_retrieve_by_id',
                        'mimeType' => 'application/ld+json',
                    ],
                    [
                        'uriTemplate' => 'http://localhost/errors/{status}',
                        'name' => 'error_retrieve_by_status',
                        'description' => 'A representation of common errors.',
                        'mimeType' => 'application/ld+json',
                    ],
                    [
                        'uriTemplate' => 'http://localhost/validation_errors/{id}',
                        'name' => 'constraintviolation_retrieve_by_id',
                        'description' => 'Unprocessable entity',
                        'mimeType' => 'application/ld+json',
                    ],
                    [
                        'uriTemplate' => 'http://localhost/contexts/{shortName}',
                        'name' => 'jsonld_context',
                        'description' => 'The JSON-LD context for a given resource short name.',
                        'mimeType' => 'application/ld+json',
                    ],
                ],
            ],
        ]);

        $arguments = [
            'name' => 'Ratatouille',
            'description' => 'A traditional French Provençal stewed vegetable dish.',
        ];
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_create',
                'arguments' => $arguments,
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $createdRecipe = $response->toArray()['result'];
        $this->assertStringContainsString('Ratatouille', $createdRecipe['content'][0]['text']);
        $this->assertArraySubset($arguments, $createdRecipe['structuredContent']);
        $createdRecipeId = $createdRecipe['structuredContent']['id'];

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/read', [
                'uri' => \sprintf('http://localhost/recipes/%d', $createdRecipeId),
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $readRecipe = $response->toArray()['result'];
        $this->assertStringContainsString('Ratatouille', $readRecipe['contents'][0]['text']);
        $this->assertArraySubset($arguments, $readRecipe['structuredContent']);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/read', [
                'uri' => 'http://localhost/recipes',
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $list = $response->toArray()['result'];
        $this->assertSame(2, $list['structuredContent']['totalItems']);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_upsert_by_id',
                'arguments' => [
                    'id' => $createdRecipeId,
                    'name' => 'Ratatouille Updated',
                    'description' => 'An updated description.',
                ],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Ratatouille Updated', $response->getContent());

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_delete_by_id',
                'arguments' => ['id' => $createdRecipeId],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $response->toArray()['result']['content'][0]['text']);
    }

    private function createJsonRpcRequest(string $method, array $params = []): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->jsonRpcId++,
            'method' => $method,
            'params' => $params,
        ];
    }
}
