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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7228\ValidationGroupSequence;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyWithCollectDenormalizationErrors;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Tests denormalization error collection feature.
 */
final class ValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyWithCollectDenormalizationErrors::class, RelatedDummy::class, ValidationGroupSequence::class];
    }

    public function testPostWithDenormalizationErrorsCollected(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $client = static::createClient();

        $response = $client->request('POST', '/dummy_collect_denormalization', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'foo' => 3,
                'bar' => 'baz',
                'qux' => true,
                'uuid' => 'y',
                'relatedDummy' => 8,
                'relatedDummies' => 76,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'hydra:title' => 'An error occurred',
        ]);

        $content = $response->toArray(false);
        $this->assertArrayHasKey('violations', $content);
        $violations = $content['violations'];
        $this->assertIsArray($violations);
        $this->assertCount(7, $violations);

        $findViolation = static function (string $propertyPath) use ($violations): ?array {
            foreach ($violations as $violation) {
                if (($violation['propertyPath'] ?? null) === $propertyPath) {
                    return $violation;
                }
            }

            return null;
        };

        $violationBaz = $findViolation('baz');
        $this->assertNotNull($violationBaz, 'Violation for "baz" not found.');
        $this->assertSame('This value should be of type string.', $violationBaz['message']);
        $this->assertArrayHasKey('hint', $violationBaz);
        $this->assertSame('Failed to create object because the class misses the "baz" property.', $violationBaz['hint']);

        $violationQux = $findViolation('qux');
        $this->assertNotNull($violationQux);

        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->assertSame('This value should be of type string.', $violationQux['message']);
        } else {
            $this->assertSame('This value should be of type null|string.', $violationQux['message']);
        }

        $violationFoo = $findViolation('foo');
        $this->assertNotNull($violationFoo);
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->assertSame('This value should be of type bool.', $violationFoo['message']);
        } else {
            $this->assertSame('This value should be of type bool|null.', $violationFoo['message']);
        }

        $violationBar = $findViolation('bar');
        $this->assertNotNull($violationBar);
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->assertSame('This value should be of type int.', $violationBar['message']);
        } else {
            $this->assertSame('This value should be of type int|null.', $violationBar['message']);
        }

        $violationUuid = $findViolation('uuid');
        $this->assertNotNull($violationUuid);
        $this->assertNotNull($violationUuid);
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $this->assertSame('This value should be of type uuid.', $violationUuid['message']);
        } else {
            $this->assertSame('This value should be of type UuidInterface|null.', $violationUuid['message']);
        }

        $violationRelatedDummy = $findViolation('relatedDummy');
        $this->assertNotNull($violationRelatedDummy);
        $this->assertSame('This value should be of type array|string.', $violationRelatedDummy['message']);

        $violationRelatedDummies = $findViolation('relatedDummies');
        $this->assertNotNull($violationRelatedDummies);
        $this->assertSame('This value should be of type array.', $violationRelatedDummies['message']);
    }

    public function testValidationGroupSequence(): void
    {
        $this->createClient()->request('POST', 'issue7228', ['headers' => ['content-type' => 'application/ld+json'], 'json' => ['id' => '1']]);
        $this->assertResponseIsSuccessful();
    }
}
