<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DeserializedQueryInput;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Query;

#[ApiResource(operations: [new Query(uriTemplate: '/deserialized_query_input', shortName: 'DeserializedQueryInput', input: DeserializedQueryCriteria::class, read: false, deserialize: true, write: true, processor: [self::class, 'process'])])]
final class DeserializedQueryInput
{
    public static mixed $processedData = null;

    public static function process(mixed $data): mixed
    {
        self::$processedData = $data;

        return $data;
    }
}
