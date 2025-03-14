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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6718\Organization;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class ItemUriTemplateTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Organization::class];
    }

    public function testIssue6718(): void
    {
        self::createClient()->request('GET', '/6718_users/1/organisation', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Not Found']);
    }
}
