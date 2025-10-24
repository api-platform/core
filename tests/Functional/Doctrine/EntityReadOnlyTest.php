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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyReadOnly;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EntityReadOnlyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyReadOnly::class];
    }

    public function testCannotUpdateOrPatchReadonlyEntity(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([DummyReadOnly::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $dummy = new DummyReadOnly();
        $dummy->setName('foo');
        $manager->persist($dummy);
        $manager->flush();

        $client = static::createClient();
        $response = $client->request('GET', '/dummy_read_onlies', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(200);

        $response = $client->request('GET', '/dummy_read_onlies/'.$dummy->getId(), ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(200);

        $response = $client->request('POST', '/dummy_read_onlies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'bar',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $response = $client->request('DELETE', '/dummy_read_onlies/'.$dummy->getId(), ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(204);

        $response = $client->request('PUT', '/dummy_read_onlies'.$dummy->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'baz',
            ],
        ]);
        $this->assertResponseStatusCodeSame(404);

        $response = $client->request('PATCH', '/dummy_read_onlies'.$dummy->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'baz',
            ],
        ]);
        $this->assertResponseStatusCodeSame(404);
    }
}
