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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLdTypeDiscriminator\ReviewActionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class TypeDiscriminatorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ReviewActionResource::class];
    }

    public static function equivalentTypesProvider(): iterable
    {
        yield 'absolute IRI' => [['@type' => 'https://schema.org/ShareAction'], 'https://schema.org/ShareAction'];
        yield 'compact IRI' => [['@context' => ['schema' => 'https://schema.org/'], '@type' => 'schema:ShareAction'], 'https://schema.org/ShareAction'];
        yield 'term with @vocab' => [['@context' => ['@vocab' => 'https://schema.org/'], '@type' => 'ShareAction'], 'https://schema.org/ShareAction'];
        yield 'term definition' => [['@context' => ['Share' => ['@id' => 'https://schema.org/ShareAction']], '@type' => 'Share'], 'https://schema.org/ShareAction'];
        yield 'context array with a remote context' => [['@context' => ['https://schema.org', ['schema' => 'https://schema.org/']], '@type' => 'schema:LikeAction'], 'https://schema.org/LikeAction'];
        yield 'array, first mapped type wins' => [['@context' => ['schema' => 'https://schema.org/'], '@type' => ['https://example.com/Unknown', 'schema:LikeAction', 'schema:ShareAction']], 'https://schema.org/LikeAction'];
    }

    #[DataProvider('equivalentTypesProvider')]
    public function testEquivalentTypesResolveTheSameClass(array $payload, string $expectedType): void
    {
        self::createClient()->request('POST', '/jsonld_type_discriminator_actions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $payload + ['object' => '/books/1'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => $expectedType,
            'actionStatus' => 'CompletedActionStatus',
            'object' => '/books/1',
        ]);
    }

    public static function invalidTypesProvider(): iterable
    {
        yield 'unresolvable term' => ['ShareAction'];
        yield 'unmapped IRI' => ['https://schema.org/BuyAction'];
        yield 'integer' => [42];
        yield 'object' => [['@id' => 'https://schema.org/ShareAction']];
        yield 'array without any mapped type' => [['https://schema.org/BuyAction', 42]];
    }

    #[DataProvider('invalidTypesProvider')]
    public function testInvalidTypeIsRejected(mixed $type): void
    {
        self::createClient()->request('POST', '/jsonld_type_discriminator_actions', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['@type' => $type, 'object' => '/books/1'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
