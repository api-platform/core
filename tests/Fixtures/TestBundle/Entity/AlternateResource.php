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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

#[Get]
#[Get('/alternate/{id}', uriVariables: ['id' => ['from_class' => AlternateResource::class, 'identifiers' => ['id']]])]
final class AlternateResource
{
    public function __construct(#[ApiProperty(identifier: true)] public string $id)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
