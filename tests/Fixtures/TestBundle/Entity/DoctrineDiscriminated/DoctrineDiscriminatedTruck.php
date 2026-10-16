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

/**
 * Not an API resource: exposed as a subtype of the vehicle resource.
 */
#[ORM\Entity]
class DoctrineDiscriminatedTruck extends DoctrineDiscriminatedVehicle
{
    #[ORM\Column(type: 'integer')]
    public int $payload = 0;
}
