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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DoctrineDiscriminatedSquare extends DoctrineDiscriminatedShape
{
    #[ORM\Column(type: 'float')]
    public float $side = 0.0;
}
