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

namespace ApiPlatform\Tests\Functional\SubResource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationMultiple;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MultipleRelationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RelationMultiple::class, Dummy::class];
    }

    public function testGetMultipleRelationItem(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('GET', '/dummy/1/relations/2', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/RelationMultiple',
            '@id' => '/dummy/1/relations/2',
            '@type' => 'RelationMultiple',
            'id' => 1,
            'first' => '/dummies/1',
            'second' => '/dummies/2',
        ]);
    }

    public function testGetMultipleRelationCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('GET', '/dummy/1/relations', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/RelationMultiple',
            '@id' => '/dummy/1/relations',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@id' => '/dummy/1/relations/2',
                    '@type' => 'RelationMultiple',
                    'id' => 1,
                    'first' => '/dummies/1',
                    'second' => '/dummies/2',
                ],
                [
                    '@id' => '/dummy/1/relations/3',
                    '@type' => 'RelationMultiple',
                    'id' => 2,
                    'first' => '/dummies/1',
                    'second' => '/dummies/3',
                ],
            ],
            'hydra:totalItems' => 2,
        ]);
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
