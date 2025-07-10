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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6225\Bar6225;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6225\Foo6225;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class NestedPatchTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Foo6225::class, Bar6225::class];
    }

    public function testIssue6225(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema(self::getResources());

        $response = self::createClient()->request('POST', '/foo6225s', [
            'json' => [
                'bar' => [
                    'someProperty' => 'abc',
                ],
            ],
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);
        static::assertResponseIsSuccessful();
        $responseContent = json_decode($response->getContent(), true);
        $createdFooId = $responseContent['id'];
        $createdBarId = $responseContent['bar']['id'];

        $patchResponse = self::createClient()->request('PATCH', '/foo6225s/'.$createdFooId, [
            'json' => [
                'bar' => [
                    'id' => $createdBarId,
                    'someProperty' => 'def',
                ],
            ],
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/merge-patch+json',
            ],
        ]);
        static::assertResponseIsSuccessful();
        static::assertEquals([
            'id' => $createdFooId,
            'bar' => [
                'id' => $createdBarId,
                'someProperty' => 'def',
            ],
        ], json_decode($patchResponse->getContent(), true));
    }
}
