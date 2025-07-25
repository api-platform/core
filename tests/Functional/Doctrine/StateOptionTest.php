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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6039\UserApi;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6039\Issue6039EntityUser;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class StateOptionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UserApi::class];
    }

    public function testDtoWithEntityClassOptionCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([Issue6039EntityUser::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $user = new Issue6039EntityUser();
        $user->name = 'name';
        $user->bar = 'bar';
        $manager->persist($user);
        $manager->flush();

        $response = static::createClient()->request('GET', '/issue6039_user_apis', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayNotHasKey('bar', $response->toArray()['hydra:member'][0]);
    }
}
