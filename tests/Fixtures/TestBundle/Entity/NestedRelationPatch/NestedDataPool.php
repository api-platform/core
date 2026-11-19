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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'nested_data_pool')]
#[ApiResource(
    operations: [
        new Get(),
        new Post(),
        new Patch(inputFormats: ['jsonld' => ['application/ld+json'], 'json' => ['application/merge-patch+json']]),
    ],
    normalizationContext: ['groups' => ['datapool:read']],
    denormalizationContext: ['groups' => ['datapool:write']],
)]
class NestedDataPool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['datapool:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ApiProperty(writableLink: true, readableLink: true)]
    #[Groups(['datapool:read', 'datapool:write'])]
    private ?NestedDataPoolStartup $dataPoolStartup = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataPoolStartup(): ?NestedDataPoolStartup
    {
        return $this->dataPoolStartup;
    }

    public function setDataPoolStartup(?NestedDataPoolStartup $dataPoolStartup): void
    {
        $this->dataPoolStartup = $dataPoolStartup;
    }
}
