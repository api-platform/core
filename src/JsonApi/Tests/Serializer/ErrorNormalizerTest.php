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

namespace ApiPlatform\JsonApi\Tests\Serializer;

use ApiPlatform\JsonApi\Serializer\ErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ErrorNormalizerTest extends TestCase
{
    public function testTypeIsOnlyExposedUnderLinks(): void
    {
        $itemNormalizer = $this->createStub(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn(['data' => [
            'id' => '/errors/500',
            'attributes' => ['type' => '/errors/500', 'title' => 'An error occurred', 'status' => 500],
        ]]);

        $errors = (new ErrorNormalizer($itemNormalizer))->normalize(new \Exception('boom'), 'jsonapi')['errors'];

        $this->assertCount(1, $errors);
        $this->assertArrayNotHasKey('type', $errors[0]);
        $this->assertSame(['type' => '/errors/500'], $errors[0]['links']);
        $this->assertSame('500', $errors[0]['status']);
    }

    public function testViolationsAreExposedWithoutTopLevelType(): void
    {
        $itemNormalizer = $this->createStub(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn(['data' => [
            'id' => '/validation_errors/abc',
            'attributes' => [
                'type' => '/validation_errors/abc',
                'title' => 'Validation Failed',
                'status' => 422,
                'violations' => [['propertyPath' => 'name', 'message' => 'This value should not be blank.']],
            ],
        ]]);

        $errors = (new ErrorNormalizer($itemNormalizer))->normalize(new \Exception('boom'), 'jsonapi')['errors'];

        $this->assertCount(1, $errors);
        $this->assertArrayNotHasKey('type', $errors[0]);
        $this->assertArrayNotHasKey('violations', $errors[0]);
        $this->assertSame(['type' => '/validation_errors/abc/name'], $errors[0]['links']);
        $this->assertSame('This value should not be blank.', $errors[0]['detail']);
    }
}
