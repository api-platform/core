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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkSchema;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/link_schema_parents/{id}',
            provider: [LinkSchemaParent::class, 'provide']
        ),
    ]
)]
class LinkSchemaParent
{
    #[ApiProperty(identifier: true)]
    public ?Uuid $id = null;

    public ?int $code = null;

    public ?string $name = null;

    public static function provide(): self
    {
        return new self();
    }
}
