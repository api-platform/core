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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

/**
 * Reproducer for https://github.com/api-platform/core/issues/7794.
 *
 * When using input DTOs with JSON:API format, the JsonApi\ItemNormalizer must
 * not unwrap the data twice. On re-entry for the input DTO, the data is already
 * flat (attributes have been extracted from the JSON:API structure).
 */
#[Post(
    uriTemplate: '/jsonapi_input_test',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    input: JsonApiInputDto::class,
    processor: [self::class, 'process'],
)]
class JsonApiInputResource
{
    public ?string $id = null;
    public string $title = '';
    public string $body = '';

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        \assert($data instanceof JsonApiInputDto);

        $resource = new self();
        $resource->id = '1';
        $resource->title = $data->title;
        $resource->body = $data->body;

        return $resource;
    }
}
