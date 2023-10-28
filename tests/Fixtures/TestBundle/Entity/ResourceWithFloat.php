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
class ResourceWithFloat
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'float')]
    private float $myFloatField = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMyFloatField(): float
    {
        return $this->myFloatField;
    }

    public function setMyFloatField(float $myFloatField): void
    {
        $this->myFloatField = $myFloatField;
    }
}
