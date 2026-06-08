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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

/**
 * Reproduces issue #8175: two operations declared with the same explicit `name`
 * but different methods on the same URI template.
 */
#[ApiResource(
    shortName: 'SameNameDifferentMethodOperations',
    operations: [
        new Post(
            uriTemplate: '/forms/{id}/submit{._format}',
            name: '_api_/forms/{id}/submit{._format}',
        ),
        new Patch(
            uriTemplate: '/forms/{id}/submit{._format}',
            name: '_api_/forms/{id}/submit{._format}',
        ),
    ],
)]
class SameNameDifferentMethodOperations
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;
}
