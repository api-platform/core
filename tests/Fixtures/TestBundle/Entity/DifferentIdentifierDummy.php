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

/**
 * Dummy with a different identifier than primary key.
 *
 * @ApiResource
 * @ORM\Entity
 */
class DifferentIdentifierDummy
{
    /**
     * @var string The database ID
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @var string The identifier
     *
     * @ORM\Column(type="string")
     *
     * @ApiProperty(identifier=true)
     */
    private $key;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }
}
