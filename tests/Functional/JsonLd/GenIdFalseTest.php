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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\AggregateRating;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\GenIdFalse;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\LevelFirst;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\LevelThird;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class GenIdFalseTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [GenIdFalse::class, AggregateRating::class, LevelFirst::class, LevelThird::class];
    }

    public function testNestedResourceWithGenIdFalseHasNoIdProperty(): void
    {
        $r = self::createClient()->request('GET', '/gen_id_falsy');
        $this->assertJsonContains([
            'aggregateRating' => ['ratingValue' => 2, 'ratingCount' => 3],
        ]);
        $this->assertArrayNotHasKey('@id', $r->toArray()['aggregateRating']);
    }

    public function testGenIdFalseAppliesOnlyToConfiguredLevel(): void
    {
        $r = self::createClient()->request('GET', '/levelfirst/1');
        $res = $r->toArray();
        $this->assertArrayNotHasKey('@id', $res['levelSecond']);
        $this->assertArrayHasKey('@id', $res['levelSecond'][0]['levelThird']);
    }
}
