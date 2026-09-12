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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DeserializedQueryInput;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Query;

#[ApiResource(operations: [new Query(uriTemplate: '/deserialized_query_input', shortName: 'DeserializedQueryInput', input: DeserializedQueryCriteria::class, read: false, deserialize: true, write: true, processor: [self::class, 'process'])])]
final class DeserializedQueryInput
{
    public static ?string $processedName = null;

    public static function process(mixed $data): mixed
    {
        if (!$data instanceof DeserializedQueryCriteria) {
            throw new \LogicException('Expected deserialized query criteria.');
        }

        self::$processedName = $data->name;

        return $data;
    }
}
