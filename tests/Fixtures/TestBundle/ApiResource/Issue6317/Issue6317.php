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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
enum Issue6317: int
{
    case First = 1;
    case Second = 2;

    #[ApiProperty(identifier: true, example: 1)]
    public function getId(): int
    {
        return $this->value;
    }

    #[ApiProperty(jsonSchemaContext: ['example' => '/lisa/mary'])]
    public function getName(): string
    {
        return $this->name;
    }

    #[ApiProperty(jsonldContext: ['example' => '24'])]
    public function getOrdinal(): string
    {
        return 1 === $this->value ? '1st' : '2nd';
    }

    #[ApiProperty(openapiContext: ['example' => 42])]
    public function getCardinal(): int
    {
        return $this->value;
    }
}
