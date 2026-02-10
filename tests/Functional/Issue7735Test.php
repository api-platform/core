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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7735\Issue7735Resource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7735\Issue7735Entity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue7735Test extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Issue7735Resource::class];
    }

    /**
     * Test that POST requests work with entities having typed properties initialized in @PrePersist.
     * This verifies the fix for issue #7735 where handleLazyObjectRelations() would fatal
     * when accessing uninitialized typed properties.
     */
    public function testPostWithUninitializedTypedPropertyInPrePersist(): void
    {
        $this->recreateSchema([Issue7735Entity::class]);

        $response = self::createClient()->request('POST', '/issue7735_resources', [
            'json' => [
                'name' => 'Test Resource',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $responseData = $response->toArray();
        $this->assertArrayHasKey('name', $responseData);
        $this->assertSame('Test Resource', $responseData['name']);
        $this->assertArrayHasKey('generatedValue', $responseData);
        $this->assertNotNull($responseData['generatedValue']);
        $this->assertStringStartsWith('generated_', $responseData['generatedValue']);
    }
}
