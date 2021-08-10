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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Metadata\Operation;

trigger_deprecation('api-platform', '2.7', sprintf('%s is deprecated, an operation can be a collection using the %s::collection property.', OperationType::class, Operation::class));

final class OperationType
{
    public const ITEM = 'item';
    public const COLLECTION = 'collection';
    public const SUBRESOURCE = 'subresource';
    public const TYPES = [self::ITEM, self::COLLECTION, self::SUBRESOURCE];
}
