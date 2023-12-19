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
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore as LegacyIgnore;
use Symfony\Component\Serializer\Annotation\SerializedName as LegacySerializedName;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[Put(
    allowCreate: true,
    extraProperties: [
        'standard_put' => true,
    ]
)]
#[ORM\Entity]
class UidIdentified
{
    /**
     * The entity ID.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[ApiProperty(identifier: false)]
    #[Ignore]
    #[LegacyIgnore]
    private ?int $id = null;

    #[ORM\Column(type: 'symfony_uuid', unique: true, nullable: false)]
    #[ApiProperty(identifier: true)]
    #[SerializedName('id')]
    #[LegacySerializedName('id')]
    private ?Uuid $uuid = null;

    /**
     * A nice person.
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    public string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }
}
