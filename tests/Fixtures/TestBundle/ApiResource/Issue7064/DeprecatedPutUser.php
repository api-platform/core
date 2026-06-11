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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7064;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['issue7064_read']],
    denormalizationContext: ['groups' => ['issue7064_update']],
    operations: [
        new Get(),
        new GetCollection(),
        new Put(deprecationReason: 'Use PATCH instead'),
        new Patch(),
    ],
)]
class DeprecatedPutUser
{
    #[Groups(['issue7064_read', 'issue7064_custom_read'])]
    public string $username = '';

    #[Groups(['issue7064_custom_update', 'issue7064_custom_read'])]
    public string $foo = '';
}
