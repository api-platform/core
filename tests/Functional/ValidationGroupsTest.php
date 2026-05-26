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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyValidation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyValidationSerializedName;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5912\Dummy as Issue5912Dummy;

final class ValidationGroupsTest extends \ApiPlatform\Symfony\Bundle\Test\ApiTestCase
{
    use \ApiPlatform\Tests\RecreateSchemaTrait;
    use \ApiPlatform\Tests\SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyValidation::class, DummyValidationSerializedName::class, Issue5912Dummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([DummyValidation::class, DummyValidationSerializedName::class]);
    }

    public function testCreateMinimalResourceWithoutGroups(): void
    {
        self::createClient()->request('POST', '/dummy_validation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['code' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testValidationGroupsTriggerFailure(): void
    {
        self::createClient()->request('POST', '/dummy_validation/validation_groups', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['code' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'detail' => 'name: This value should not be null.',
            'violations' => [[
                'propertyPath' => 'name',
                'message' => 'This value should not be null.',
                'code' => 'ad32d13f-c3d4-423b-909a-857b961eb720',
            ]],
        ]);
    }

    public function testValidationGroupSequence(): void
    {
        self::createClient()->request('POST', '/dummy_validation/validation_sequence', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['code' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'detail' => 'title: This value should not be null.',
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value should not be null.',
                'code' => 'ad32d13f-c3d4-423b-909a-857b961eb720',
            ]],
        ]);
    }

    public function testValidationUsesSerializedNameForPropertyPath(): void
    {
        $response = self::createClient()->request('POST', '/dummy_validation_serialized_name', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['code' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $data = $response->toArray(false);
        $this->assertSame('test: This value should not be null.', $data['detail']);
        $this->assertSame('test', $data['violations'][0]['propertyPath']);
        $this->assertSame('This value should not be null.', $data['violations'][0]['message']);
    }

    public function testGetViolationConstraints(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('POST', '/issue5912s', [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'json' => ['title' => ''],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonEquals([
            'status' => 422,
            'violations' => [[
                'propertyPath' => 'title',
                'message' => 'This value should not be blank.',
                'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            ]],
            'detail' => 'title: This value should not be blank.',
            'type' => '/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            'title' => 'An error occurred',
        ]);
    }
}
