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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EntityClassWithDateTime;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EntityClassWithDateTime as EntityClassWithDateTimeEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EntityClassWithDateTimeTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [EntityClassWithDateTime::class];
    }

    public function testGetExposesDateTimeProperty(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([EntityClassWithDateTimeEntity::class]);

        $manager = $this->getManager();
        $entity = new EntityClassWithDateTimeEntity();
        $entity->setStart(new \DateTime('2024-05-12T10:00:00+00:00'));
        $manager->persist($entity);
        $manager->flush();

        $response = self::createClient()->request('GET', '/EntityClassWithDateTime/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();
        $this->assertSame('/EntityClassWithDateTime/1', $body['@id']);
        $this->assertSame('EntityClassWithDateTime', $body['@type']);
        $this->assertArrayHasKey('start', $body);
        $this->assertNotEmpty($body['start']);
    }
}
