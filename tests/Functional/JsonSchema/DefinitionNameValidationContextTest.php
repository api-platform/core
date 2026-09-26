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

namespace ApiPlatform\Tests\Functional\JsonSchema;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyValidation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyValidationReversedOrder;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DefinitionNameValidationContextTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyValidation::class, DummyValidationReversedOrder::class];
    }

    public function testValidationContextProducesDistinctDefinitions(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();

        [$barePostName, $bareRequired] = $this->postSchema($json, 'dummy_validation');
        [$groupsPostName, $groupsRequired] = $this->postSchema($json, 'dummy_validation/validation_groups');

        $this->assertNotSame($groupsPostName, $barePostName, 'The bare POST and the POST with a validationContext must not share a schema definition name.');
        $this->assertNotContains('name', $bareRequired, 'The bare POST has no validationContext, "name" must not be required.');
        $this->assertContains('name', $groupsRequired, 'The POST with validationContext groups=["a"] must require "name".');
    }

    public function testValidationContextDefinitionsAreOrderIndependent(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();

        [$groupsPostName, $groupsRequired] = $this->postSchema($json, 'dummy_validation_reversed_order/validation_groups');
        [$barePostName, $bareRequired] = $this->postSchema($json, 'dummy_validation_reversed_order');

        $this->assertNotSame($groupsPostName, $barePostName, 'The bare POST and the POST with a validationContext must not share a schema definition name, regardless of declaration order.');
        $this->assertNotContains('name', $bareRequired, 'The bare POST has no validationContext, "name" must not be required, regardless of declaration order.');
        $this->assertContains('name', $groupsRequired, 'The POST with validationContext groups=["a"] must require "name", regardless of declaration order.');
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function postSchema(array $json, string $uriFragment): array
    {
        $path = null;
        foreach (array_keys($json['paths']) as $candidate) {
            if (str_contains($candidate, $uriFragment) && isset($json['paths'][$candidate]['post'])) {
                $path = $candidate;
                break;
            }
        }

        $this->assertNotNull($path, \sprintf('No POST path matching "%s" found in the OpenAPI document.', $uriFragment));

        $ref = $json['paths'][$path]['post']['requestBody']['content']['application/ld+json']['schema']['$ref'] ?? null;
        $this->assertNotNull($ref, \sprintf('POST "%s" has no request body schema reference.', $path));

        $name = substr($ref, \strlen('#/components/schemas/'));
        $this->assertArrayHasKey($name, $json['components']['schemas'], \sprintf('Schema "%s" is missing from components.', $name));

        return [$name, $json['components']['schemas'][$name]['required'] ?? []];
    }
}
