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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8834Resource;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue8834InvalidIriTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Issue8834Resource::class];
    }

    protected function setUp(): void
    {
        if ('mongodb' === ($_SERVER['APP_ENV'] ?? null)) {
            $this->markTestSkipped('This test uses a Doctrine ORM fixture.');
        }

        $this->recreateSchema(self::getResources());
    }

    public function testInvalidIriIsIgnored(): void
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $related = new Issue8834Resource();
        $manager->persist($related);
        $resource = new Issue8834Resource();
        $resource->related = $related;
        $manager->persist($resource);
        $manager->flush();

        self::createClient()->request('GET', '/issue8834_resources?related=/invalid/iri');

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains(['hydra:totalItems' => 0]);
    }
}
