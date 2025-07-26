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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue4372\RelatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue4372\ToOneRelationPropertyMayBeNull;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class NotSkipNullToOneRelationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ToOneRelationPropertyMayBeNull::class, RelatedEntity::class];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function testNullRelationsAreNotSkippedWhenConfigured(): void
    {
        $itemIri = str_replace(
            '{id}',
            ToOneRelationPropertyMayBeNull::ENTITY_ID.'',
            ToOneRelationPropertyMayBeNull::ITEM_ROUTE
        );
        $this->checkRoutesAreCorrectlySetUp();

        self::createClient()->request(
            'GET',
            $itemIri,
            [
                'headers' => [
                    'accept' => 'application/hal+json',
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $this->assertJsonEquals(
            [
                '_links' => [
                    'self' => [
                        'href' => $itemIri,
                    ],
                    'relatedEntity' => null,
                    'relatedEntity2' => [
                        'href' => '/related_entities/1',
                    ],
                ],
                'collection' => [
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/1',
                            ],
                        ],
                        'id' => 1,
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/2',
                            ],
                        ],
                        'id' => 2,
                    ],
                ],
            ]
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function testNullRelationsAreSkippedByDefault(): void
    {
        $itemIri = str_replace(
            '{id}',
            ToOneRelationPropertyMayBeNull::ENTITY_ID.'',
            ToOneRelationPropertyMayBeNull::ITEM_SKIP_NULL_TO_ONE_RELATION_ROUTE
        );
        $this->checkRoutesAreCorrectlySetUp();

        self::createClient()->request(
            'GET',
            $itemIri,
            [
                'headers' => [
                    'accept' => 'application/hal+json',
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $this->assertJsonEquals(
            [
                '_links' => [
                    'self' => [
                        'href' => $itemIri,
                    ],
                    'relatedEntity2' => [
                        'href' => '/related_entities/1',
                    ],
                ],
                'collection' => [
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/1',
                            ],
                        ],
                        'id' => 1,
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/2',
                            ],
                        ],
                        'id' => 2,
                    ],
                ],
            ]
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function checkRoutesAreCorrectlySetUp(): void
    {
        self::createClient()->request(
            'GET',
            '/',
            [
                'headers' => [
                    'accept' => 'application/hal+json',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                '_links' => [
                    'relatedEntity' => [
                        'href' => '/related_entities',
                    ],
                    'toOneRelationPropertyMayBeNull' => [
                        'href' => ToOneRelationPropertyMayBeNull::ROUTE,
                    ],
                ],
            ]
        );
    }
}
