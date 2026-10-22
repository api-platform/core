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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PatchableDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UpdateConflictTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [PatchableDummy::class];
    }

    public function testPatchWithMatchingIdSucceeds(): void
    {
        self::createClient()->request('PATCH', '/jsonapi_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'JsonApiPatchableDummy', 'id' => '/jsonapi_patchable_dummies/1', 'attributes' => ['name' => 'changed']]],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testPatchWithMismatchedIdIsAConflict(): void
    {
        self::createClient()->request('PATCH', '/jsonapi_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'JsonApiPatchableDummy', 'id' => '/jsonapi_patchable_dummies/2', 'attributes' => ['name' => 'changed']]],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testPatchWithMismatchedTypeIsAConflictWhenStrict(): void
    {
        self::createClient()->request('PATCH', '/jsonapi_strict_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'SomethingElse', 'id' => '/jsonapi_patchable_dummies/1', 'attributes' => ['name' => 'changed']]],
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testPatchWithForeignTypeIsAcceptedByDefault(): void
    {
        self::createClient()->request('PATCH', '/jsonapi_patchable_dummies/1', [
            'headers' => ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'SomethingElse', 'id' => '/jsonapi_patchable_dummies/1', 'attributes' => ['name' => 'changed']]],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
