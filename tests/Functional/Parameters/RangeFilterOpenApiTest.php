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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RangeFilterOpenApiTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SoMany::class];
    }

    public function testOpenApiParameters(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The SoMany document declares its filters with #[ApiFilter].');
        }

        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $names = array_column($response->toArray()['paths']['/so_manies']['get']['parameters'], 'name');

        foreach (['id[gt]', 'id[lt]', 'id[gte]', 'id[lte]', 'id[between]'] as $name) {
            $this->assertContains($name, $names);
        }
    }
}
