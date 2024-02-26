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

use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[Put(
    extraProperties: [
        'standard_put' => true,
    ],
    allowCreate: true
)]
class DummyMappedSubclass extends DummyMappedSuperclass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
