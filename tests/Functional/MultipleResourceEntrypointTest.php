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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MultipleResourceBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Functional test for entrypoint with multiple ApiResource declarations.
 *
 * Tests that when a resource has multiple #[ApiResource] attributes,
 * both are properly exposed in the entrypoint, context, and documentation,
 * and that duplicate shortNames are suffixed with a number.
 */
class MultipleResourceEntrypointTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MultipleResourceBook::class];
    }

    /**
     * Test that /contexts/Entrypoint exposes both resource shortNames.
     *
     * The first resource keeps the class shortName (MultipleResourceBook),
     * the second is suffixed (MultipleResourceBook2).
     */
    public function testEntrypointContextExposesMultipleResources(): void
    {
        $response = self::createClient()->request('GET', '/contexts/Entrypoint', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('@context', $data);
        $context = $data['@context'];

        $this->assertArrayHasKey('multipleResourceBook', $context);
        $this->assertIsArray($context['multipleResourceBook']);
        $this->assertEquals('Entrypoint/multipleResourceBook', $context['multipleResourceBook']['@id']);
        $this->assertEquals('@id', $context['multipleResourceBook']['@type']);

        $this->assertArrayHasKey('multipleResourceBook2', $context);
        $this->assertIsArray($context['multipleResourceBook2']);
        $this->assertEquals('Entrypoint/multipleResourceBook2', $context['multipleResourceBook2']['@id']);
        $this->assertEquals('@id', $context['multipleResourceBook2']['@type']);
    }

    /**
     * Test that /index.jsonld (the entrypoint) exposes both routes.
     */
    public function testEntrypointExposesMultipleRoutes(): void
    {
        $response = self::createClient()->request('GET', '/index.jsonld', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('multipleResourceBook', $data);
        $this->assertEquals('/admin/multi_route_books', $data['multipleResourceBook']);

        $this->assertArrayHasKey('multipleResourceBook2', $data);
        $this->assertEquals('/multi_route_books', $data['multipleResourceBook2']);
    }

    /**
     * Test that /docs.jsonld documents both resources as supported classes.
     */
    public function testDocumentationExposesMultipleResourcesAsSupportedClasses(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:supportedClass', $data);
        $supportedClasses = $data['hydra:supportedClass'];

        $firstResourceFound = false;
        $secondResourceFound = false;

        foreach ($supportedClasses as $supportedClass) {
            if (isset($supportedClass['hydra:title']) && 'MultipleResourceBook' === $supportedClass['hydra:title']) {
                $firstResourceFound = true;
                $this->assertArrayHasKey('hydra:supportedOperation', $supportedClass);
            }
            if (isset($supportedClass['hydra:title']) && 'MultipleResourceBook2' === $supportedClass['hydra:title']) {
                $secondResourceFound = true;
                $this->assertArrayHasKey('hydra:supportedOperation', $supportedClass);
            }
        }

        $this->assertTrue($firstResourceFound, 'MultipleResourceBook should be in hydra:supportedClass');
        $this->assertTrue($secondResourceFound, 'MultipleResourceBook2 should be in hydra:supportedClass');
    }
}
