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

namespace ApiPlatform\Tests\Functional\MongoDb;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NestedReferenceFilterErrorTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class];
    }

    protected function setUp(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('Requires APP_ENV=mongodb.');
        }
        $this->recreateSchema([Dummy::class, RelatedDummy::class, ThirdLevel::class, FourthLevel::class]);

        $manager = $this->getManager();

        $fourthLevel = new FourthLevel();
        $fourthLevel->setLevel(4);
        $manager->persist($fourthLevel);

        $thirdLevel = new ThirdLevel();
        $thirdLevel->setLevel(3);
        $thirdLevel->setFourthLevel($fourthLevel);
        $manager->persist($thirdLevel);

        $namedRelatedDummy = new RelatedDummy();
        $namedRelatedDummy->setName('Hello');
        $namedRelatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($namedRelatedDummy);

        $relatedDummy = new RelatedDummy();
        $relatedDummy->setThirdLevel($thirdLevel);
        $manager->persist($relatedDummy);

        $dummy = new Dummy();
        $dummy->setName('Dummy with relations');
        $dummy->setRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($namedRelatedDummy);
        $dummy->addRelatedDummy($relatedDummy);
        $manager->persist($dummy);

        $manager->flush();
        $manager->clear();
    }

    public function testOwningSideBadReferenceTriggers500(): void
    {
        $response = self::createClient()->request('GET', '/dummies?relatedDummy.thirdLevel.badFourthLevel.level=4', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(500);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('/contexts/Error', $body['@context']);
        $this->assertSame('hydra:Error', $body['@type']);
        $this->assertSame("Cannot use reference 'badFourthLevel' in class 'ThirdLevel' for lookup or graphLookup: dbRef references are not supported.", $body['detail']);
        $this->assertArrayHasKey('trace', $body);
    }

    public function testNonOwningSideBadReferenceTriggers500(): void
    {
        $response = self::createClient()->request('GET', '/dummies?relatedDummy.thirdLevel.fourthLevel.badThirdLevel.level=3', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(500);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('/contexts/Error', $body['@context']);
        $this->assertSame('hydra:Error', $body['@type']);
        $this->assertSame("Cannot use reference 'badThirdLevel' in class 'FourthLevel' for lookup or graphLookup: dbRef references are not supported.", $body['detail']);
        $this->assertArrayHasKey('trace', $body);
    }
}
