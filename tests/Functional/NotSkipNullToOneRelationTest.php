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

    public function testNullRelationsAreNotSkippedWhenConfigured(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }
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
                '_embedded' => [
                    'relatedEmbeddedEntity' => null,
                    'relatedEmbeddedEntity2' => [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/1',
                            ],
                        ],
                        'id' => 1,
                    ],
                ],
                '_links' => [
                    'self' => [
                        'href' => '/my-route/1',
                    ],
                    'relatedEmbeddedEntity' => null,
                    'relatedEmbeddedEntity2' => [
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
                'id' => 1,
            ]
        );
    }

    public function testNullRelationsAreSkippedByDefault(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }
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
                '_embedded' => [
                    'relatedEmbeddedEntity2' => [
                        '_links' => [
                            'self' => [
                                'href' => '/related_entities/1',
                            ],
                        ],
                        'id' => 1,
                    ],
                ],
                '_links' => [
                    'self' => [
                        'href' => '/skip-null-relation-route/1',
                    ],
                    'relatedEmbeddedEntity2' => [
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
                'id' => 1,
            ]
        );
    }

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

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
