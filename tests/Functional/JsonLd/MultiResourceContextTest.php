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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiResourceEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class MultiResourceContextTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MultiResourceEntity::class];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([MultiResourceEntity::class]);

        $manager = $this->getManager();
        $multi = new MultiResourceEntity();
        $multi->title = 'Multi Resource';
        $manager->persist($multi);
        $manager->flush();
    }

    public function testContextUsesShortNameForCurrentResourceVariant(): void
    {
        $response = self::createClient()->request('GET', '/multi_resources');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/MultiResource',
        ]);

        $response = self::createClient()->request('GET', '/admin/multi_resources');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/AdminMultiResource',
        ]);
    }
}
