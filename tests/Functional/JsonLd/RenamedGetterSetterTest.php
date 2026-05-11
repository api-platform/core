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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\RenamedGetterSetter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class RenamedGetterSetterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RenamedGetterSetter::class];
    }

    public function testPostExposesRenamedField(): void
    {
        $response = self::createClient()->request('POST', '/json_ld_renamed_getter_setters', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['firstnameOnly' => 'Sarah'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([
            '@context' => '/contexts/JsonLdRenamedGetterSetter',
            '@id' => '/json_ld_renamed_getter_setters',
            '@type' => 'JsonLdRenamedGetterSetter',
            'firstnameOnly' => 'Sarah',
        ], $response->toArray());
    }
}
