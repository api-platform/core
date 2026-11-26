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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

/**
 * Class-based resource enabling the runtime resources in tests:
 * they are only declared when this class is part of the resource name collection.
 */
#[Get(uriTemplate: '/dynamic_resource_hosts/{id}', provider: [self::class, 'provide'])]
final class DynamicResourceHost
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public static function provide(): self
    {
        return new self();
    }
}
