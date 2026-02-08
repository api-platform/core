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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DateTimeNormalizationIssue;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that ItemNormalizer does not intercept DateTimeImmutable objects
 * before Symfony's DateTimeNormalizer when context leaks through custom normalizers.
 *
 * @see https://github.com/api-platform/core/issues/7733
 */
final class DateTimeNormalizerPriorityTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DateTimeNormalizationIssue::class];
    }

    public function testDateTimeImmutableIsNormalizedAsString(): void
    {
        $response = self::createClient()->request('GET', '/datetime_normalization_issues/1');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();

        $this->assertSame(1, $data['id']);
        $this->assertSame('Test Resource', $data['name']);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertIsString($data['updatedAt']);
        $this->assertStringContainsString('2024-01-15', $data['updatedAt']);
    }

    /**
     * Tests that ItemNormalizer::supportsNormalization returns false for DateTimeImmutable
     * even when force_resource_class leaks through context from a parent resource normalization.
     *
     * This reproduces the bug in https://github.com/api-platform/core/issues/7733
     * where a custom normalizer delegates DateTimeImmutable normalization to the serializer
     * with a context containing force_resource_class, causing ItemNormalizer to intercept it.
     */
    public function testDateTimeImmutableIsNotInterceptedByItemNormalizer(): void
    {
        self::bootKernel();
        $serializer = self::getContainer()->get('serializer');

        // Simulate a custom normalizer that delegates DateTimeImmutable normalization
        // with a context that still contains force_resource_class from the parent resource
        $dateTime = new \DateTimeImmutable('2024-01-15T10:30:00+00:00');
        $result = $serializer->normalize($dateTime, 'jsonld', [
            'force_resource_class' => DateTimeNormalizationIssue::class,
        ]);

        // DateTimeNormalizer should handle this, producing a string
        // ItemNormalizer must NOT intercept it (which would throw a LogicException)
        $this->assertIsString($result);
        $this->assertStringContainsString('2024-01-15', $result);
    }
}
