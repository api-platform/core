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

namespace ApiPlatform\Tests\Functional\MongoDb;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyWithEmbedManyOmittingTargetDocument;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EmbedManyWithoutTargetDocumentTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DummyWithEmbedManyOmittingTargetDocument::class];
    }

    protected function setUp(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('Requires APP_ENV=mongodb.');
        }
        // @todo Re-enable once the union-typed `array|Collection $embeddedDummies` property is
        //       denormalized without "Could not denormalize object of type Collection".
        $this->markTestSkipped('Pending serializer fix for union-typed EmbedMany properties.');
    }

    public function testPostHydratesEmbedManyWithoutTargetDocument(): void
    {
        self::createClient()->request(
            'POST',
            '/dummy_with_embed_many_omitting_target_documents',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode([
                    'embeddedDummies' => [
                        ['dummyName' => 'foo', 'dummyBoolean' => true, 'dummyDate' => '2020-01-01', 'dummyFloat' => 0.1, 'dummyPrice' => 10],
                        ['dummyName' => 'bar', 'dummyBoolean' => false, 'dummyDate' => '2021-01-01', 'dummyFloat' => 0.2, 'dummyPrice' => 20],
                    ],
                ]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/DummyWithEmbedManyOmittingTargetDocument',
            '@id' => '/dummy_with_embed_many_omitting_target_documents/1',
            '@type' => 'DummyWithEmbedManyOmittingTargetDocument',
            'id' => 1,
            'embeddedDummies' => [
                ['@type' => 'EmbeddableDummy', 'dummyName' => 'foo', 'dummyBoolean' => true, 'dummyDate' => '2020-01-01T00:00:00+00:00', 'dummyFloat' => 0.1, 'dummyPrice' => 10],
                ['@type' => 'EmbeddableDummy', 'dummyName' => 'bar', 'dummyBoolean' => false, 'dummyDate' => '2021-01-01T00:00:00+00:00', 'dummyFloat' => 0.2, 'dummyPrice' => 20],
            ],
        ]);
    }
}
