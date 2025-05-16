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

namespace ApiPlatform\JsonSchema\Tests;

use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DtoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DefinitionNameFactoryTest extends TestCase
{
    public static function providerDefinitions(): iterable
    {
        yield ['Dummy', Dummy::class, 'json'];
        yield ['Dummy.jsonapi', Dummy::class, 'jsonapi'];
        yield ['Dummy.jsonhal', Dummy::class, 'jsonhal'];
        yield ['Dummy.jsonld', Dummy::class, 'jsonld'];

        yield ['Dummy.DtoOutput', Dummy::class, 'json', DtoOutput::class];
        yield ['Dummy.DtoOutput.jsonapi', Dummy::class, 'jsonapi', DtoOutput::class];
        yield ['Dummy.DtoOutput.jsonhal', Dummy::class, 'jsonhal', DtoOutput::class];
        yield ['Dummy.DtoOutput.jsonld', Dummy::class, 'jsonld', DtoOutput::class];

        yield ['Bar', Dummy::class, 'json', null, new Get(shortName: 'Bar')];
        yield ['Bar.jsonapi', Dummy::class, 'jsonapi', null, new Get(shortName: 'Bar')];
        yield ['Bar.jsonhal', Dummy::class, 'jsonhal', null, new Get(shortName: 'Bar')];
        yield ['Bar.jsonld', Dummy::class, 'jsonld', null, new Get(shortName: 'Bar')];

        yield ['Dummy-Baz', Dummy::class, 'json', null, null, [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Baz']];
        yield ['Dummy.jsonapi-Baz', Dummy::class, 'jsonapi', null, null, [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Baz']];
        yield ['Dummy.jsonhal-Baz', Dummy::class, 'jsonhal', null, null, [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Baz']];
        yield ['Dummy.jsonld-Baz', Dummy::class, 'jsonld', null, null, [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Baz']];

        yield ['Dummy-read', Dummy::class, 'json', null, null, [AbstractNormalizer::GROUPS => ['read']]];
        yield ['Dummy.jsonapi-read', Dummy::class, 'jsonapi', null, null, [AbstractNormalizer::GROUPS => ['read']]];
        yield ['Dummy.jsonhal-read', Dummy::class, 'jsonhal', null, null, [AbstractNormalizer::GROUPS => ['read']]];
        yield ['Dummy.jsonld-read', Dummy::class, 'jsonld', null, null, [AbstractNormalizer::GROUPS => ['read']]];

        yield ['Dummy-read_write', Dummy::class, 'json', null, null, [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Dummy.jsonapi-read_write', Dummy::class, 'jsonapi', null, null, [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Dummy.jsonhal-read_write', Dummy::class, 'jsonhal', null, null, [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Dummy.jsonld-read_write', Dummy::class, 'jsonld', null, null, [AbstractNormalizer::GROUPS => ['read', 'write']]];

        yield ['Bar.DtoOutput-read_write', Dummy::class, 'json', DtoOutput::class, new Get(shortName: 'Bar'), [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Bar.DtoOutput.jsonapi-read_write', Dummy::class, 'jsonapi', DtoOutput::class, new Get(shortName: 'Bar'), [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Bar.DtoOutput.jsonhal-read_write', Dummy::class, 'jsonhal', DtoOutput::class, new Get(shortName: 'Bar'), [AbstractNormalizer::GROUPS => ['read', 'write']]];
        yield ['Bar.DtoOutput.jsonld-read_write', Dummy::class, 'jsonld', DtoOutput::class, new Get(shortName: 'Bar'), [AbstractNormalizer::GROUPS => ['read', 'write']]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerDefinitions')]
    public function testCreate(string $expected, string $className, string $format = 'json', ?string $inputOrOutputClass = null, ?Operation $operation = null, array $serializerContext = []): void
    {
        $definitionNameFactory = new DefinitionNameFactory();

        static::assertSame($expected, $definitionNameFactory->create($className, $format, $inputOrOutputClass, $operation, $serializerContext));
    }

    public function testCreateDifferentPrefixesForClassesWithTheSameShortName(): void
    {
        $definitionNameFactory = new DefinitionNameFactory();

        self::assertEquals(
            'DummyClass.jsonapi',
            $definitionNameFactory->create(Fixtures\DefinitionNameFactory\NamespaceA\Module\DummyClass::class, 'jsonapi')
        );

        self::assertEquals(
            'Module.DummyClass.jsonapi',
            $definitionNameFactory->create(Fixtures\DefinitionNameFactory\NamespaceB\Module\DummyClass::class, 'jsonapi')
        );

        self::assertEquals(
            'NamespaceC.Module.DummyClass.jsonapi',
            $definitionNameFactory->create(Fixtures\DefinitionNameFactory\NamespaceC\Module\DummyClass::class, 'jsonapi')
        );

        self::assertEquals(
            'DummyClass.jsonhal',
            $definitionNameFactory->create(Fixtures\DefinitionNameFactory\NamespaceA\Module\DummyClass::class, 'jsonhal')
        );
    }
}
