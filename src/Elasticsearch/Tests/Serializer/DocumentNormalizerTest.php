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

namespace ApiPlatform\Elasticsearch\Tests\Serializer;

use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;

final class DocumentNormalizerTest extends TestCase
{
    public function testConstruct(): void
    {
        $normalizer = new DocumentNormalizer();

        self::assertInstanceOf(NormalizerInterface::class, $normalizer);
        self::assertInstanceOf(SerializerAwareInterface::class, $normalizer);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new DocumentNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new Foo(), DocumentNormalizer::FORMAT));
        self::assertFalse($normalizer->supportsNormalization(new Foo(), 'json'));
        self::assertFalse($normalizer->supportsNormalization('not an object', DocumentNormalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $normalizer = new DocumentNormalizer();

        $foo = new Foo();
        $foo->setName('Test');
        $foo->setBar('Value');

        $result = $normalizer->normalize($foo, DocumentNormalizer::FORMAT);

        self::assertIsArray($result);
        self::assertSame('Test', $result['name']);
        self::assertSame('Value', $result['bar']);
    }

    public function testNormalizeWithId(): void
    {
        $normalizer = new DocumentNormalizer();

        // Use anonymous class with id to test _id/_source wrapping
        $object = new class {
            public int $id = 1;
            public string $name = 'Test';
        };

        $result = $normalizer->normalize($object, DocumentNormalizer::FORMAT);

        self::assertIsArray($result);
        self::assertArrayHasKey('_id', $result);
        self::assertArrayHasKey('_source', $result);
        self::assertSame('1', $result['_id']);
        self::assertSame(1, $result['_source']['id']);
        self::assertSame('Test', $result['_source']['name']);
    }

    public function testGetSupportedTypes(): void
    {
        $normalizer = new DocumentNormalizer();

        self::assertSame(['object' => true], $normalizer->getSupportedTypes(DocumentNormalizer::FORMAT));
        self::assertSame([], $normalizer->getSupportedTypes('json'));
    }
}
