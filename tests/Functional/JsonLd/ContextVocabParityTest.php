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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\GroupExcludedPropertyResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ContextVocabParityTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [GroupExcludedPropertyResource::class];
    }

    public function testPropertyExcludedFromEveryGroupIsAbsentFromHydraVocab(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();

        $resource = null;
        foreach ($body['hydra:supportedClass'] as $class) {
            if ('GroupExcludedProperty' === ($class['hydra:title'] ?? null)) {
                $resource = $class;
                break;
            }
        }
        $this->assertNotNull($resource);

        $propertyTitles = array_column($resource['hydra:supportedProperty'], 'hydra:title');
        $this->assertContains('exposed', $propertyTitles);
        $this->assertNotContains('notInAnyGroup', $propertyTitles);
    }

    public function testPropertyExcludedFromEveryGroupIsAbsentFromJsonLdContext(): void
    {
        $response = self::createClient()->request('GET', '/contexts/GroupExcludedProperty');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertArrayHasKey('exposed', $body['@context']);
        $this->assertArrayNotHasKey('notInAnyGroup', $body['@context']);
    }
}
