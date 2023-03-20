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
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ArrayRequiredFilter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Secured resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(
    security: 'is_granted(\'ROLE_USER\')',
    filters: [ArrayRequiredFilter::class],
)]
#[ORM\Entity]
class SecuredDummyWithFilter
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
