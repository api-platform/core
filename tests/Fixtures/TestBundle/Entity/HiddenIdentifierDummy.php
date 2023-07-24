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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations            : [
        new Get(),
        new Put(allowCreate: true),
    ],
    normalizationContext  : ['groups' => ['HiddenIdentifierDummy::out']],
    denormalizationContext: ['groups' => ['HiddenIdentifierDummy::in']],
    extraProperties       : [
        'standard_put' => true,
        'ormIds' => ['id'],
    ],
)]
#[Entity]
class HiddenIdentifierDummy
{
    #[Id]
    #[Column]
    #[ApiProperty(identifier: false)]
    public ?int $id = null;

    #[Column]
    #[ApiProperty(identifier: true)]
    #[Groups(['HiddenIdentifierDummy::out'])]
    public ?string $visibleId = null;

    #[Column]
    #[Groups(['HiddenIdentifierDummy::out', 'HiddenIdentifierDummy::in'])]
    public string $foo = '';
}
