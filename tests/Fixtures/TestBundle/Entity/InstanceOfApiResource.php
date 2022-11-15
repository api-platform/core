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

use ApiPlatform\Tests\Fixtures\TestBundle\Attributes\RestfulApi;
use Doctrine\ORM\Mapping as ORM;

#[RestfulApi]
#[ORM\Entity]
class InstanceOfApiResource
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }
}
