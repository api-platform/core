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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EnumValidationResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * @see https://github.com/api-platform/core/issues/8183
 */
final class EnumDenormalizationValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [EnumValidationResource::class];
    }

    protected function setUp(): void
    {
        // On symfony/property-info < 7.1, nullable backed enums resolve to a
        // single legacy Type instead of a UnionType. AbstractItemNormalizer
        // then does not re-wrap the BackedEnumNormalizer exception with the
        // enum FQCN, and DeserializeProvider cannot detect it as a BackedEnum
        // failure. The 400 response is expected on those versions.
        if (version_compare(ltrim(InstalledVersions::getPrettyVersion('symfony/property-info') ?? '0', 'v'), '7.1.0', '<')) {
            $this->markTestSkipped('Requires symfony/property-info >= 7.1 for BackedEnum type detection.');
        }
    }

    public function testInvalidBackedEnumValueProducesValidationViolation(): void
    {
        $response = static::createClient()->request('POST', '/enum_validation_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['gender' => 'unknown'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $content = $response->toArray(false);
        $this->assertArrayHasKey('violations', $content);
        $this->assertNotEmpty($content['violations']);

        $genderViolation = $this->findViolation($content['violations'], 'gender');
        $this->assertNotNull($genderViolation, 'Expected a constraint violation on "gender" property.');
    }

    #[IgnoreDeprecations]
    public function testInvalidBackedEnumValueWithCollectDenormalizationErrors(): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'symfony/serializer', '>=8.1')) {
            $this->expectUserDeprecationMessage('Since symfony/serializer 8.1: The "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getErrors()" method is deprecated, use "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getNotNormalizableValueErrors()" instead.');
        }

        $response = static::createClient()->request('POST', '/enum_validation_resources_collect', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['gender' => 'unknown'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $content = $response->toArray(false);
        $this->assertNotNull($this->findViolation($content['violations'] ?? [], 'gender'));
    }

    /**
     * @see https://github.com/api-platform/core/issues/8388
     */
    #[IgnoreDeprecations]
    public function testWrongTypeForBackedEnumReportsAcceptedScalarTypes(): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'symfony/serializer', '>=8.1')) {
            $this->expectUserDeprecationMessage('Since symfony/serializer 8.1: The "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getErrors()" method is deprecated, use "Symfony\Component\Serializer\Exception\PartialDenormalizationException::getNotNormalizableValueErrors()" instead.');
        }

        $response = static::createClient()->request('POST', '/enum_validation_resources_collect', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['gender' => true],
        ]);

        $this->assertResponseStatusCodeSame(422);

        $content = $response->toArray(false);
        $genderViolation = $this->findViolation($content['violations'] ?? [], 'gender');
        $this->assertNotNull($genderViolation);
        // The message must name what a JSON consumer can actually send: the enum's backing scalar
        // ("string") and, since the property is nullable, "null" — never the internal PHP type
        // "GenderTypeEnum|null", which appears nowhere in the OpenAPI schema.
        $this->assertSame('This value should be of type string|null.', $genderViolation['message']);
        $this->assertStringNotContainsString('GenderTypeEnum', $genderViolation['message']);
        $this->assertStringContainsString('enumeration case of type', $genderViolation['hint'] ?? '');
    }

    private function findViolation(array $violations, string $propertyPath): ?array
    {
        foreach ($violations as $violation) {
            if (($violation['propertyPath'] ?? null) === $propertyPath) {
                return $violation;
            }
        }

        return null;
    }
}
