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
use Symfony\Component\Uid\Uuid;

#[ApiResource(filters: ['my_dummy.uuid_range'])]
#[ORM\Entity]
class DummyUuidV6
{

    #[ORM\Column(type: 'symfony_uuid')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
