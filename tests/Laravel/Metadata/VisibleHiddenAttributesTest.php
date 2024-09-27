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

namespace ApiPlatform\Tests\Laravel\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Oitzinger <tobiasoitzinger@gmail.com>
 */
class VisibleHiddenAttributesTest extends TestCase
{
    private ModelMetadata $modelMetadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelMetadata = new ModelMetadata();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testHiddenAttributesAreCorrectlyIdentified(): void
    {
        $model = new class extends Model {
            protected $hidden = ['secret'];
        };

        $result = $this->invokePrivateMethod($this->modelMetadata, 'attributeIsHidden', ['secret', $model]);
        $this->assertTrue($result);

        $result = $this->invokePrivateMethod($this->modelMetadata, 'attributeIsHidden', ['public', $model]);
        $this->assertFalse($result);
    }

    public function testVisibleAttributesAreCorrectlyIdentified(): void
    {
        $model = new class extends Model {
            protected $visible = ['public'];
        };

        $result = $this->invokePrivateMethod($this->modelMetadata, 'attributeIsHidden', ['secret', $model]);
        $this->assertTrue($result);

        $result = $this->invokePrivateMethod($this->modelMetadata, 'attributeIsHidden', ['public', $model]);
        $this->assertFalse($result);
    }

    public function testAllAttributesVisibleByDefault(): void
    {
        $model = new class extends Model {};

        $result = $this->invokePrivateMethod($this->modelMetadata, 'attributeIsHidden', ['any_attribute', $model]);
        $this->assertFalse($result);
    }

    public function testGetRelationsReturnsCorrectRelations(): void
    {
        $relation = \Mockery::mock(HasMany::class);
        $relation2 = \Mockery::mock(HasMany::class);

        $model = new class($relation, $relation2) extends Model {
            protected $relation1;
            protected $relation2;
            protected $hidden = ['roles'];

            public function __construct($relation1, $relation2)
            {
                $this->relation1 = $relation1;
                $this->relation2 = $relation2;
            }

            public function posts(): HasMany
            {
                return $this->relation1;
            }

            public function roles(): HasMany
            {
                return $this->relation2;
            }
        };

        $relation->shouldReceive('getRelated')->andReturn($model);
        $relation->shouldReceive('getForeignKeyName')->andReturn('post_id');

        $relation2->shouldReceive('getRelated')->andReturn($model);
        $relation2->shouldReceive('getForeignKeyName')->andReturn('role_id');

        // Mock the ReflectionMethod to return `false` for getFileName to avoid file reading.
        $reflectionMock = \Mockery::mock(\ReflectionMethod::class)->makePartial();
        $reflectionMock->shouldReceive('getFileName')->andReturn(false);

        $relations = $this->modelMetadata->getRelations($model);

        $this->assertCount(1, $relations);
        $this->assertEquals('posts', $relations->first()['name']);
    }

    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
