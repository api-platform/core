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

namespace ApiPlatform\GraphQl\Tests\Type;

use ApiPlatform\GraphQl\Type\TypeNotFoundException;
use ApiPlatform\GraphQl\Type\TypesContainer;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypesContainerTest extends TestCase
{
    use ProphecyTrait;

    private TypesContainer $typesContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typesContainer = new TypesContainer();
    }

    public function testSet(): void
    {
        $type = $this->prophesize(GraphQLType::class)->reveal();

        $this->typesContainer->set('test', $type);
        $this->assertSame($type, $this->typesContainer->get('test'));
    }

    public function testGet(): void
    {
        $type = $this->prophesize(GraphQLType::class)->reveal();

        $this->typesContainer->set('test', $type);
        $this->assertSame($type, $this->typesContainer->get('test'));
    }

    public function testGetTypeNotFound(): void
    {
        $this->expectException(TypeNotFoundException::class);
        $this->expectExceptionMessage('Type with id "test" is not present in the types container');

        $this->typesContainer->get('test');
    }

    public function testAll(): void
    {
        $type = $this->prophesize(GraphQLType::class)->reveal();

        $this->typesContainer->set('test', $type);
        $this->assertEquals(['test' => $type], $this->typesContainer->all());
    }

    public function testHas(): void
    {
        $type = $this->prophesize(GraphQLType::class)->reveal();

        $this->typesContainer->set('test', $type);
        $this->assertTrue($this->typesContainer->has('test'));
    }
}
