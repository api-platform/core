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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7426;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[GetCollection(
    normalizationContext: ['groups' => ['boat:read']],
)]
class Boat
{
    #[ApiProperty(identifier: true)]
    #[Groups(['boat:read'])]
    public int $id;

    #[Groups(['boat:read'])]
    public string $name;
}
