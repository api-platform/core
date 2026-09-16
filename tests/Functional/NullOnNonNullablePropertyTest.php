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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\NullOnNonNullableProperty\NullOnNonNullableResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/** @see https://github.com/symfony/symfony/issues/64159 */
final class NullOnNonNullablePropertyTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [NullOnNonNullableResource::class];
    }

    public function testNullOnNonNullablePropertyReturns400(): void
    {
        $response = self::createClient()->request('POST', '/null_on_non_nullable_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => null],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $body = $response->toArray(false);
        $this->assertStringContainsString('Expected argument of type "string", "null" given at property path "name"', $body['hydra:description'] ?? $body['detail'] ?? '');
    }

    #[IgnoreDeprecations]
    public function testNullOnNonNullablePropertyReturns422WhenCollectingErrors(): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'symfony/serializer', '>=8.1')) {
            $this->expectUserDeprecationMessage('Since symfony/serializer 8.1: The "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getErrors()" method is deprecated, use "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getNotNormalizableValueErrors()" instead.');
        }

        $response = self::createClient()->request('POST', '/null_on_non_nullable_resources_collect', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => null],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');

        $content = $response->toArray(false);
        $this->assertArrayHasKey('violations', $content);
        $this->assertSame('name', $content['violations'][0]['propertyPath']);
    }
}
