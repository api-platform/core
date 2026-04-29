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

namespace ApiPlatform\Laravel\Tests\Eloquent\Serializer\Mapping\Loader;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Eloquent\Serializer\Mapping\Loader\RelationMetadataLoader;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Workbench\App\Models\AbstractModel;

class RelationMetadataLoaderTest extends TestCase
{
    /**
     * @see https://github.com/api-platform/core/issues/7911
     */
    public function testLoadClassMetadataReturnsFalseForAbstractModelWithoutInstantiating(): void
    {
        $loader = new RelationMetadataLoader(new ModelMetadata());

        $result = $loader->loadClassMetadata(new ClassMetadata(AbstractModel::class));

        $this->assertFalse($result);
    }
}
