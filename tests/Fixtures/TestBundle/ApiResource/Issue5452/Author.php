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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5452\AuthorItemProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/issue-5452/authors/{id}{._format}', provider: AuthorItemProvider::class),
    ]
)]
class Author implements ActivableInterface, TimestampableInterface
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly string|int $id,
        public readonly string $name
    ) {
    }
}
