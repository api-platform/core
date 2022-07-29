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
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Entity]
class VoDummyInsuranceCompany
{
    use VoDummyIdAwareTrait;

    public function __construct(#[ORM\Column] #[Groups(['car_read', 'car_write'])] private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
