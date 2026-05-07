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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\ImageModuleResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\PageResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\TitleModuleResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class PolymorphicResourceCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PageResource::class, TitleModuleResource::class, ImageModuleResource::class];
    }

    public function testPolymorphicCollectionPropertyExposesPerItemTypes(): void
    {
        self::createClient()->request('GET', '/page_resources/page-1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'modules' => [
                [
                    '@type' => 'TitleModuleResource',
                    'id' => 'title-module-1',
                    'title' => 'My Title',
                ],
                [
                    '@type' => 'ImageModule',
                    'id' => 'image-module-1',
                    'url' => 'http://example.com/image.jpg',
                ],
            ],
        ]);
    }
}
