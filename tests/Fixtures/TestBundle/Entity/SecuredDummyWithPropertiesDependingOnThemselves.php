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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * Secured resource with properties depending on themselves.
 *
 * @author Loïc Boursin <contact@loicboursin.fr>
 */
#[ApiResource(
    operations: [
        new Get(),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json'], 'jsonapi']),
        new Post(security: 'is_granted("ROLE_ADMIN")'),
    ]
)]
#[ORM\Entity]
class SecuredDummyWithPropertiesDependingOnThemselves
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    private bool $canUpdateProperty = false;

    /**
     * @var bool Special property, only writable when granted rights by another property
     */
    #[ApiProperty(securityPostDenormalize: 'previous_object and previous_object.getCanUpdateProperty()')]
    #[ORM\Column]
    private bool $property = false;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCanUpdateProperty(): bool
    {
        return $this->canUpdateProperty;
    }

    public function setCanUpdateProperty(bool $canUpdateProperty): void
    {
        $this->canUpdateProperty = $canUpdateProperty;
    }

    public function getProperty(): bool
    {
        return $this->property;
    }

    public function setProperty(bool $property): void
    {
        $this->property = $property;
    }
}
