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

namespace ApiPlatform\Tests\Functional\Json;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6358\OutputAndEntityClass;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OutputAndEntityClassTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [OutputAndEntityClass::class];
    }

    public function testCollectionUsesEntityClassFromStateOptionsForType(): void
    {
        if ('mongodb' === static::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        self::createClient()->request('GET', '/output_and_entity_classes', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'hydra:member' => [
                ['@type' => 'OutputAndEntityClassEntity'],
            ],
        ]);
    }
}
