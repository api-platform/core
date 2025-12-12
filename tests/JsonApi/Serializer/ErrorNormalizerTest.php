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

namespace ApiPlatform\Tests\JsonApi\Serializer;

use ApiPlatform\JsonApi\Serializer\ErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Tests for the JSON API ErrorNormalizer.
 */
final class ErrorNormalizerTest extends TestCase
{
    /**
     * Test normalization when attributes are missing from the normalized structure.
     * This can occur with ItemNotFoundException or similar exceptions.
     * The normalizer should handle this gracefully and return a valid JSON:API error.
     */
    public function testNormalizeWithMissingAttributes(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'id' => 'error-1',
                'type' => 'errors',
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $exception = new \Exception('Test error');

        $result = $errorNormalizer->normalize($exception, 'jsonapi');

        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('error-1', $result['errors'][0]['id']);
        $this->assertEquals('Test error', $result['errors'][0]['title']);
        $this->assertArrayHasKey('status', $result['errors'][0]);
    }

    /**
     * Test the normal case with properly structured normalized data.
     */
    public function testNormalizeWithValidStructure(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'type' => 'errors',
                'id' => 'error-1',
                'attributes' => [
                    'title' => 'An error occurred',
                    'detail' => 'Something went wrong',
                    'status' => '500',
                ],
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $result = $errorNormalizer->normalize(new \Exception('Test error'), 'jsonapi');

        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('error-1', $result['errors'][0]['id']);
        $this->assertEquals('An error occurred', $result['errors'][0]['title']);
        $this->assertEquals('Something went wrong', $result['errors'][0]['detail']);
        $this->assertIsString($result['errors'][0]['status']);
    }

    /**
     * Test with violations in the error attributes.
     */
    public function testNormalizeWithViolations(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'type' => 'errors',
                'id' => 'validation-error',
                'attributes' => [
                    'title' => 'Validation failed',
                    'detail' => 'Invalid input',
                    'status' => 422,
                    'violations' => [
                        [
                            'message' => 'This field is required',
                            'propertyPath' => 'name',
                        ],
                        [
                            'message' => 'Invalid email format',
                            'propertyPath' => 'email',
                        ],
                    ],
                ],
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $result = $errorNormalizer->normalize(new \Exception('Validation error'), 'jsonapi');

        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(2, $result['errors']);
        $this->assertEquals('This field is required', $result['errors'][0]['detail']);
        $this->assertEquals('Invalid email format', $result['errors'][1]['detail']);
        $this->assertFalse(isset($result['errors'][0]['violations']));
        $this->assertIsInt($result['errors'][0]['status']);
        $this->assertEquals(422, $result['errors'][0]['status']);
    }

    /**
     * Test with type and links generation.
     */
    public function testNormalizeWithTypeGeneratesLinks(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'type' => 'errors',
                'id' => 'about:blank/errors/validation',
                'attributes' => [
                    'type' => 'about:blank/errors/validation',
                    'title' => 'Validation Error',
                    'detail' => 'Input validation failed',
                    'status' => '422',
                    'violations' => [
                        [
                            'message' => 'Must be a number',
                            'propertyPath' => 'age',
                        ],
                    ],
                ],
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $result = $errorNormalizer->normalize(new \Exception('Validation'), 'jsonapi');

        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(1, $result['errors']);
        $this->assertArrayHasKey('links', $result['errors'][0]);
        $this->assertStringContainsString('age', $result['errors'][0]['links']['type']);
    }

    public function testJsonApiComplianceForMissingAttributesCase(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'id' => 'error-123',
                'type' => 'errors',
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $result = $errorNormalizer->normalize(new \Exception('Not found'), 'jsonapi');

        $this->assertArrayHasKey('errors', $result, 'Response must have "errors" key at top level');
        $this->assertIsArray($result['errors'], '"errors" must be an array');
        $this->assertNotEmpty($result['errors'], '"errors" array must not be empty');

        $error = $result['errors'][0];
        $this->assertIsArray($error, 'Each error must be an object/array');

        $hasAtLeastOneMember = isset($error['id']) || isset($error['links']) || isset($error['status'])
            || isset($error['code']) || isset($error['title']) || isset($error['detail'])
            || isset($error['source']) || isset($error['meta']);

        $this->assertTrue($hasAtLeastOneMember, 'Error object must contain at least one of: id, links, status, code, title, detail, source, meta');

        if (isset($error['status'])) {
            $this->assertIsString($error['status'], '"status" must be a string value');
        }

        if (isset($error['code'])) {
            $this->assertIsString($error['code'], '"code" must be a string value');
        }

        if (isset($error['links'])) {
            $this->assertIsArray($error['links'], '"links" must be an object');
        }
    }

    public function testJsonApiComplianceForNormalCase(): void
    {
        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn([
            'data' => [
                'type' => 'errors',
                'id' => 'error-456',
                'attributes' => [
                    'title' => 'Validation Failed',
                    'detail' => 'The request body is invalid',
                    'status' => '422',
                    'code' => 'validation_error',
                ],
            ],
        ]);

        $errorNormalizer = new ErrorNormalizer($itemNormalizer);
        $result = $errorNormalizer->normalize(new \Exception('Validation error'), 'jsonapi');

        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);

        $error = $result['errors'][0];
        $this->assertIsArray($error);

        $hasAtLeastOneMember = isset($error['id']) || isset($error['links']) || isset($error['status'])
            || isset($error['code']) || isset($error['title']) || isset($error['detail'])
            || isset($error['source']) || isset($error['meta']);

        $this->assertTrue($hasAtLeastOneMember, 'Error object must contain at least one required member');

        $this->assertEquals('error-456', $error['id']);
        $this->assertEquals('Validation Failed', $error['title']);
        $this->assertEquals('The request body is invalid', $error['detail']);
        $this->assertEquals('422', $error['status']);
        $this->assertEquals('validation_error', $error['code']);
    }
}
