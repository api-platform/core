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
 * Resource using an input DTO with required constructor arguments.
 *
 * Tests that all missing constructor arguments are reported (not just the first).
 */
#[Post(
    uriTemplate: '/jsonapi_required_fields_test',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    input: JsonApiRequiredFieldsInputDto::class,
    processor: [self::class, 'process'],
)]
class JsonApiRequiredFieldsResource
{
    public ?string $id = null;
    public string $title = '';
    public int $rating = 0;
    public string $comment = '';

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        \assert($data instanceof JsonApiRequiredFieldsInputDto);

        $resource = new self();
        $resource->id = '1';
        $resource->title = $data->title;
        $resource->rating = $data->rating;
        $resource->comment = $data->comment;

        return $resource;
    }
}
