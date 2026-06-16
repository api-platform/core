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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedRelationPatch;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'nested_data_pool_startup')]
#[ApiResource]
class NestedDataPoolStartup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['datapool:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ApiProperty(writableLink: true, readableLink: true)]
    #[Groups(['datapool:read', 'datapool:write'])]
    private ?NestedStartup $startup = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartup(): ?NestedStartup
    {
        return $this->startup;
    }

    public function setStartup(?NestedStartup $startup): void
    {
        $this->startup = $startup;
    }
}
