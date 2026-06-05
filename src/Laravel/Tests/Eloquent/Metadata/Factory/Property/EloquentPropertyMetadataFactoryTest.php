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

namespace ApiPlatform\Laravel\Tests\Eloquent\Metadata\Factory\Property;

use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\EnumType;

enum CastEnumIntStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 0;
}

enum CastEnumUnitStatus
{
    case ACTIVE;
    case INACTIVE;
}

class CastEnumStringStatusModel extends Model
{
    protected $table = 'books';

    protected function casts(): array
    {
        return [
            'status' => BookStatus::class,
        ];
    }
}

class CastEnumIntStatusModel extends Model
{
    protected $table = 'books';

    protected function casts(): array
    {
        return [
            'status' => CastEnumIntStatus::class,
        ];
    }
}

class CastEnumUnitStatusModel extends Model
{
    protected $table = 'books';

    protected function casts(): array
    {
        return [
            'status' => CastEnumUnitStatus::class,
        ];
    }
}

/**
 * @see https://github.com/api-platform/core/issues/8138
 */
final class EloquentPropertyMetadataFactoryTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testStringBackedEnumCastIsMappedToEnumType(): void
    {
        $factory = new EloquentPropertyMetadataFactory(new ModelMetadata());
        $metadata = $factory->create(CastEnumStringStatusModel::class, 'status');

        $type = $metadata->getNativeType();
        $this->assertInstanceOf(BackedEnumType::class, $type);
        $this->assertSame(BookStatus::class, $type->getClassName());
    }

    public function testIntBackedEnumCastIsMappedToEnumType(): void
    {
        $factory = new EloquentPropertyMetadataFactory(new ModelMetadata());
        $metadata = $factory->create(CastEnumIntStatusModel::class, 'status');

        $type = $metadata->getNativeType();
        $this->assertInstanceOf(BackedEnumType::class, $type);
        $this->assertSame(CastEnumIntStatus::class, $type->getClassName());
    }

    public function testUnitEnumCastIsMappedToEnumType(): void
    {
        $factory = new EloquentPropertyMetadataFactory(new ModelMetadata());
        $metadata = $factory->create(CastEnumUnitStatusModel::class, 'status');

        $type = $metadata->getNativeType();
        $this->assertInstanceOf(EnumType::class, $type);
        $this->assertSame(CastEnumUnitStatus::class, $type->getClassName());
    }
}
