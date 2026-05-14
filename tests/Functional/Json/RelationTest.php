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

namespace ApiPlatform\Tests\Functional\Json;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RelationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [ThirdLevel::class, RelatedDummy::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with MongoDB.');
        }

        $this->recreateSchema([ThirdLevel::class, RelatedDummy::class]);
    }

    public function testCreateRelatedDummyWithPlainIdentifierForRelation(): void
    {
        // Creates a ThirdLevel; PurgeHttpCacheListener::postFlush caches the GetCollection
        // operation under the '' + ThirdLevel + '_c' slot of IriConverter::$localOperationCache.
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['level' => 3],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // RelatedDummyPlainIdentifierDenormalizer calls getIriFromResource(ThirdLevel::class, new Get(), …).
        // Without the fix the '_c' slot collision returns the GetCollection op, producing
        // "/third_levels?id=1" instead of "/third_levels/1".
        $response = self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['thirdLevel' => '1'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $data = $response->toArray(false);
        $this->assertArrayHasKey('thirdLevel', $data);
        $this->assertIsArray($data['thirdLevel']);
        $this->assertSame('/third_levels/1', $data['thirdLevel']['@id']);
    }
}
