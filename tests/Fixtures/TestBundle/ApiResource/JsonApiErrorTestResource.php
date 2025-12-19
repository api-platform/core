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

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    uriTemplate: '/jsonapi_error_test/{id}',
    provider: [self::class, 'provide'],
    formats: ['jsonapi' => ['application/vnd.api+json']],
)]
class JsonApiErrorTestResource
{
    public string $id;
    public string $name;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = $uriVariables['id'] ?? null;

        if ('existing' === $id) {
            $resource = new self();
            $resource->id = $id;
            $resource->name = 'Existing Resource';

            return $resource;
        }

        throw new ItemNotFoundException(\sprintf('Resource "%s" not found.', $id));
    }
}
