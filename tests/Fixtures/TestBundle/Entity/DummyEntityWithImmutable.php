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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy entity with attributes made immutable through serialization groups.
 *
 * @author Karsten Lehmann <mail@kalehmann.de>
 *
 * @ORM\Entity
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"dummy_read"}},
 *         "denormalization_context"={"groups"={"dummy_edit"}},
 *     },
 *     collectionOperations={
 *         "get",
 *         "post"={
 *             "denormalization_context"={"groups"={"dummy_add"}}
 *         }
 *     }
 * )
 */
class DummyEntityWithImmutable
{
    /**
     * @var string The name
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @ApiProperty(
     *     attributes={
     *         "swagger_context"={"example"="Günther Meyer"}
     *     }
     * )
     *
     * @Groups({"dummy_read", "dummy_add"})
     */
    private $immutableName;

    /**
     * @var string The website
     *
     * @ORM\Column(type="string")
     *
     * @ApiProperty(
     *     attributes={
     *         "swagger_context"={"example"="katzensaft.de"}
     *     }
     * )
     *
     * @Groups({"dummy_read", "dummy_add", "dummy_edit"})
     */
    private $mutableWebsite;

    public function __construct($immutableName)
    {
        $this->immutableName = $immutableName;
    }

    public function getImmutableName(): string
    {
        return $this->immutableName;
    }

    public function setImmutableName(string $immutableName): void
    {
        $this->immutableName = $immutableName;
    }

    public function getMutableWebsite(): string
    {
        return $this->mutableWebsite;
    }

    public function setMutableWebsite(string $mutableWebsite): void
    {
        $this->mutableWebsite = $mutableWebsite;
    }
}
