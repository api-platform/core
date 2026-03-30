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

namespace ApiPlatform\Tests\Functional\HttpCache;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PushRelationsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Dummy::class, RelatedDummy::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('HTTP/2 push only enabled on SQLite test suite');
        }

        $this->recreateSchema([Dummy::class, RelatedDummy::class]);
        $this->loadDummies(2);
    }

    public function testCollectionPushesRelatedIris(): void
    {
        self::createClient()->request('GET', '/dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseHeaderSame(
            'Link',
            '</related_dummies/1>; rel="preload"; as="fetch",</related_dummies/2>; rel="preload"; as="fetch",<http://localhost/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"',
        );
    }

    public function testItemPushesRelatedIri(): void
    {
        self::createClient()->request('GET', '/dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseHeaderSame(
            'Link',
            '</related_dummies/1>; rel="preload"; as="fetch",<http://localhost/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"',
        );
    }

    private function loadDummies(int $count): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();

        for ($i = 1; $i <= $count; ++$i) {
            $related = new RelatedDummy();
            $related->setName('RelatedDummy #'.$i);

            $dummy = new Dummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }

        $manager->flush();
    }
}
