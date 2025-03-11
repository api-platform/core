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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HideHydraClass;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HideHydraOperation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class HydraTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [HideHydraOperation::class, HideHydraClass::class];
    }

    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testIssue6465(): void
    {
        $response = self::createClient()->request('GET', 'docs', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        foreach ($response->toArray()['hydra:supportedClass'] as $supportedClass) {
            $this->assertNotEquals($supportedClass['hydra:title'], 'HideHydraClass');
            if ('HideHydraOperation' === $supportedClass['hydra:title']) {
                $this->assertEmpty($supportedClass['hydra:supportedOperation']);
            }
        }

        $response = self::createClient()->request('GET', 'index', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);
        $this->assertArrayNotHasKey('hideHydraOperation', $response->toArray());
    }
}
