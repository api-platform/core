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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\PropertyInfo;

use ApiPlatform\Core\Bridge\Eloquent\PropertyInfo\EloquentExtractor;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy as DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EloquentExtractorTest extends TestCase
{
    use ProphecyTrait;

    private $eloquentExtractor;

    protected function setUp(): void
    {
        $this->eloquentExtractor = new EloquentExtractor();
    }

    public function testGetTypesNotModel(): void
    {
        self::assertNull($this->eloquentExtractor->getTypes(NotAResource::class, 'foo'));
    }

    public function testGetTypesNoApiProperties(): void
    {
        self::assertNull($this->eloquentExtractor->getTypes(DummyEntity::class, 'foo'));
    }

    /**
     * @dataProvider provideGetTypesCases
     */
    public function testGetTypes(string $property, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->eloquentExtractor->getTypes(Dummy::class, $property));
    }

    public function provideGetTypesCases(): \Generator
    {
        yield 'not a relation' => ['foo', null];

        yield 'relation' => ['relatedDummy', [new Type(
            Type::BUILTIN_TYPE_OBJECT,
            false,
            RelatedDummy::class
        )]];

        yield 'relation many' => ['relatedDummies', [new Type(
            Type::BUILTIN_TYPE_OBJECT,
            false,
            Collection::class,
            true,
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
        )]];
    }

    public function testIsReadable(): void
    {
        self::assertNull($this->eloquentExtractor->isReadable(Dummy::class, 'foo'));
    }

    /**
     * @dataProvider provideIsWritableCases
     */
    public function testIsWritable(string $class, string $property, ?bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->eloquentExtractor->isWritable($class, $property));
    }

    public function provideIsWritableCases(): \Generator
    {
        yield 'not eloquent model' => [NotAResource::class, 'foo', null];
        yield 'identifier' => [Dummy::class, 'id', null];
        yield 'property' => [Dummy::class, 'foo', true];
    }
}
