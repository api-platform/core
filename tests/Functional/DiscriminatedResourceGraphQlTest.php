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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedBus;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GarageWithPolymorphicVehicle\Garage;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlInputDefaultTags;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DiscriminatedResourceGraphQlTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return DiscriminatorMapAppKernel::class;
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DiscriminatedVehicle::class, DiscriminatedBus::class, GraphQlInputDefaultTags::class, Garage::class];
    }

    public function testPolymorphicResourcesAreNotExposedInTheSchema(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => '{ __schema { queryType { fields { name } } mutationType { fields { name } } } }',
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));

        $fields = array_map(static fn (array $field): string => $field['name'], [...$json['data']['__schema']['queryType']['fields'], ...$json['data']['__schema']['mutationType']['fields']]);
        $this->assertContains('graphQlInputDefaultTags', $fields);
        foreach ($fields as $field) {
            $this->assertStringNotContainsStringIgnoringCase('discriminated', $field);
        }
    }

    public function testRelationToAPolymorphicResourceKeepsTheSchemaValid(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => '{ __type(name: "Garage") { fields { name type { name kind } } } garage(id: "/garages/1") { id vehicle { name } } }',
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame(['id', '_id', 'vehicle', 'vehicles'], array_column($json['data']['__type']['fields'], 'name'));
        $this->assertSame('DiscriminatedVehicle', $json['data']['__type']['fields'][2]['type']['name']);
        $this->assertSame('DiscriminatedVehicleCursorConnection', $json['data']['__type']['fields'][3]['type']['name']);
        $this->assertSame('Beetle', $json['data']['garage']['vehicle']['name']);
    }
}
