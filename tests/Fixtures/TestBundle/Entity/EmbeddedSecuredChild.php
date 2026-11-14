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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [new Get()],
    normalizationContext: ['groups' => ['embedded_secured:read']],
)]
#[ORM\Entity]
class EmbeddedSecuredChild
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'children')]
    public ?EmbeddedSecuredChildParent $parent = null;

    #[ORM\Column]
    #[Groups(['embedded_secured:read'])]
    public string $name = '';

    #[ORM\Column]
    public string $owner = '';

    #[ORM\Column]
    #[ApiProperty(security: 'object.owner == user.getUserIdentifier()')]
    #[Groups(['embedded_secured:read'])]
    public string $secret = '';
}
