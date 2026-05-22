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

namespace ApiPlatform\Tests\Functional\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ArrayFilterValidator;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterValidator;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validation built from legacy filter descriptions registered through the
 * `filters` attribute on the resource. The QueryParameter equivalent is
 * covered by {@see \ApiPlatform\Tests\Functional\Parameters\ValidationTest}.
 */
final class FilterValidationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [FilterValidator::class, ArrayFilterValidator::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema($this->getResources());
    }

    public function testRequiredFilterValid(): void
    {
        self::createClient()->request('GET', '/filter_validators?required=foo&required-allow-empty=&arrayRequired[foo]=', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testRequiredFilterBlank(): void
    {
        self::createClient()->request('GET', '/filter_validators?required=&required-allow-empty=&arrayRequired[foo]=', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['detail' => 'required: This value should not be blank.']);
    }

    public function testRequiredFilterMissing(): void
    {
        self::createClient()->request('GET', '/filter_validators', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'detail' => "required: This value should not be blank.\nrequired-allow-empty: This value should not be null.",
        ]);
    }

    public function testArrayRequiredValid(): void
    {
        self::createClient()->request('GET', '/array_filter_validators?arrayRequired[]=foo&indexedArrayRequired[foo]=foo', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testArrayRequiredMissing(): void
    {
        self::createClient()->request('GET', '/array_filter_validators', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'detail' => "arrayRequired[]: This value should not be blank.\nindexedArrayRequired[foo]: This value should not be blank.",
        ]);
    }

    public function testArrayRequiredOnlyOneKeyProvided(): void
    {
        self::createClient()->request('GET', '/array_filter_validators?arrayRequired[foo]=foo', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'detail' => 'indexedArrayRequired[foo]: This value should not be blank.',
        ]);

        self::createClient()->request('GET', '/array_filter_validators?arrayRequired[]=foo', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'detail' => 'indexedArrayRequired[foo]: This value should not be blank.',
        ]);
    }

    public static function bounds(): iterable
    {
        yield 'maximum valid' => ['maximum=10', 200, null];
        yield 'maximum invalid' => ['maximum=11', 422, 'maximum: This value should be less than or equal to 10.'];
        yield 'exclusiveMaximum valid' => ['exclusiveMaximum=9', 200, null];
        yield 'exclusiveMaximum invalid' => ['exclusiveMaximum=10', 422, 'exclusiveMaximum: This value should be less than 10.'];
        yield 'minimum valid' => ['minimum=5', 200, null];
        yield 'minimum invalid' => ['minimum=0', 422, 'minimum: This value should be greater than or equal to 5.'];
        yield 'exclusiveMinimum valid' => ['exclusiveMinimum=6', 200, null];
        yield 'exclusiveMinimum invalid' => ['exclusiveMinimum=5', 422, 'exclusiveMinimum: This value should be greater than 5.'];
        yield 'max length valid' => ['max-length-3=123', 200, null];
        yield 'max length invalid' => ['max-length-3=1234', 422, 'max-length-3: This value is too long. It should have 3 characters or less.'];
        yield 'min length valid' => ['min-length-3=123', 200, null];
        yield 'min length invalid' => ['min-length-3=12', 422, 'min-length-3: This value is too short. It should have 3 characters or more.'];
        yield 'pattern valid' => ['pattern=nrettap', 200, null];
        yield 'pattern invalid' => ['pattern=not-pattern', 422, 'pattern: This value is not valid.'];
        yield 'enum valid' => ['enum=in-enum', 200, null];
        yield 'enum invalid' => ['enum=not-in-enum', 422, 'enum: The value you selected is not a valid choice.'];
        yield 'multipleOf valid' => ['multiple-of=4', 200, null];
        yield 'multipleOf invalid' => ['multiple-of=3', 422, 'multiple-of: This value should be a multiple of 2.'];
    }

    #[DataProvider('bounds')]
    public function testFilterBounds(string $extraQuery, int $expectedStatus, ?string $expectedDetail): void
    {
        $url = '/filter_validators?required=foo&required-allow-empty&'.$extraQuery;

        self::createClient()->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame($expectedStatus);
        if (null !== $expectedDetail) {
            $this->assertJsonContains(['detail' => $expectedDetail]);
        }
    }
}
