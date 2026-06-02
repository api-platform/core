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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DenormalizationValidationResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @see https://github.com/api-platform/core/issues/7981
 */
final class DenormalizationValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DenormalizationValidationResource::class];
    }

    public function testNullOnNotBlankPropertyProduces422WithNotBlankViolation(): void
    {
        $response = static::createClient()->request('POST', '/denormalization_validation_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => null],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $content = $response->toArray(false);
        $violation = $this->findViolation($content['violations'] ?? [], 'name');
        $this->assertNotNull($violation, 'Expected a violation on "name".');
        $this->assertSame((string) NotBlank::IS_BLANK_ERROR, $violation['code'] ?? null);
    }

    public function testNullOnNotNullPropertyProduces422WithNotNullViolation(): void
    {
        $response = static::createClient()->request('POST', '/denormalization_validation_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['description' => null],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $content = $response->toArray(false);
        $violation = $this->findViolation($content['violations'] ?? [], 'description');
        $this->assertNotNull($violation, 'Expected a violation on "description".');
        $this->assertSame((string) NotNull::IS_NULL_ERROR, $violation['code'] ?? null);
    }

    public function testWrongTypeOnTypeConstrainedPropertyProduces422WithTypeViolation(): void
    {
        $response = static::createClient()->request('POST', '/denormalization_validation_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['score' => 'abc'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $content = $response->toArray(false);
        $violation = $this->findViolation($content['violations'] ?? [], 'score');
        $this->assertNotNull($violation, 'Expected a violation on "score".');
        $this->assertSame((string) Type::INVALID_TYPE_ERROR, $violation['code'] ?? null);
    }

    public function testWrongTypeWithoutConstraintProduces400(): void
    {
        $response = static::createClient()->request('POST', '/denormalization_validation_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['rawFloat' => 'abc'],
        ]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCollectMixedConstrainedAndUnconstrainedProduces422WithSpecificCodes(): void
    {
        $response = static::createClient()->request('POST', '/denormalization_validation_resources_collect', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => null,
                'score' => 'abc',
                'rawFloat' => 'abc',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $content = $response->toArray(false);
        $violations = $content['violations'] ?? [];

        $nameViolation = $this->findViolation($violations, 'name');
        $this->assertNotNull($nameViolation);
        $this->assertSame((string) NotBlank::IS_BLANK_ERROR, $nameViolation['code'] ?? null);

        $scoreViolation = $this->findViolation($violations, 'score');
        $this->assertNotNull($scoreViolation);
        $this->assertSame((string) Type::INVALID_TYPE_ERROR, $scoreViolation['code'] ?? null);

        // Unconstrained property still translates to a generic Type violation in collect mode
        // (consistent with prior behavior — collect mode never re-throws single errors).
        $rawFloatViolation = $this->findViolation($violations, 'rawFloat');
        $this->assertNotNull($rawFloatViolation);
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
