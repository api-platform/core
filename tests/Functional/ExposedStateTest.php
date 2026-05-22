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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TruncatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ExposedStateTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [TruncatedDummy::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        if (!$this->isPostgres()) {
            $this->markTestSkipped('Decimal truncation is enforced by Postgres only.');
        }

        $this->recreateSchema($this->getResources());
    }

    public function testCreateReturnsTruncatedValue(): void
    {
        self::createClient()->request('POST', '/truncated_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['value' => '20.3325'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/TruncatedDummy',
            '@id' => '/truncated_dummies/1',
            '@type' => 'TruncatedDummy',
            'value' => '20.3',
            'id' => 1,
        ]);
    }

    public function testUpdateReturnsTruncatedValue(): void
    {
        $client = self::createClient();
        $client->request('POST', '/truncated_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['value' => '20.3325'],
        ]);

        $client->request('PUT', '/truncated_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['value' => '42.42'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/TruncatedDummy',
            '@id' => '/truncated_dummies/1',
            '@type' => 'TruncatedDummy',
            'value' => '42.4',
            'id' => 1,
        ]);
    }
}
