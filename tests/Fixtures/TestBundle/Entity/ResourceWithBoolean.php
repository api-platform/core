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

#[ApiResource]
#[ORM\Entity]
class ResourceWithBoolean
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'boolean')]
    private bool $myBooleanField = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMyBooleanField(): bool
    {
        return $this->myBooleanField;
    }

    public function setMyBooleanField(bool $myBooleanField): void
    {
        $this->myBooleanField = $myBooleanField;
    }
}
