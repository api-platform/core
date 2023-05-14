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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ApiResource]
#[ORM\Entity]
class RamseyUuidDummy
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    /**
     * The dummy id.
     */
    private UuidInterface $id;
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?UuidInterface $other = null;

    public function __construct(?UuidInterface $id = null)
    {
        $this->id = $id ?? Uuid::uuid4();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getOther(): ?UuidInterface
    {
        return $this->other;
    }

    public function setOther(UuidInterface $other): void
    {
        $this->other = $other;
    }
}
