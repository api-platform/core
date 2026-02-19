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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\AggregateRating;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\GenIdDefault;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\GenIdTrue;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class GenIdGlobalOptionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            AggregateRating::class,
            GenIdDefault::class,
            GenIdTrue::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['GEN_ID_DEFAULT']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['GEN_ID_DEFAULT']);
        parent::tearDown();
    }

    /**
     * When gen_id is globally false and no #[ApiProperty(genId: ...)] on the property,
     * the nested object must not expose an @id.
     */
    public function testGlobalGenIdFalseDisablesSkolemIdByDefaultOnProperties(): void
    {
        $_SERVER['GEN_ID_DEFAULT'] = 0; // simulate global defaults.normalization_context.gen_id: false

        $response = self::createClient()->request(
            'GET',
            '/gen_id_default'
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayNotHasKey('@id', $data['subresources'][0]);
    }

    /**
     * #[ApiProperty(genId: true)] on the property must take precedence.
     */
    public function testApiPropertyGenIdTrueTakesPrecedenceOverGlobalFalse(): void
    {
        $_SERVER['GEN_ID_DEFAULT'] = 0; // simulate global defaults.normalization_context.gen_id: false

        $response = self::createClient()->request(
            'GET',
            '/gen_id_truthy'
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('@id', $data['subresources'][0]);
    }

    /**
     * Without a global option and without an attribute, genId must be true by default.
     */
    public function testWhenNoGlobalOptionAndNoAttributeGenIdIsTrueByDefault(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/gen_id_default'
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('@id', $data['subresources'][0]);
    }
}
