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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\UuidRangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ApiResource(
    paginationViaCursor: [
        ['field' => 'id', 'direction' => 'DESC']
    ],
    paginationPartial: true
)]
#[ApiFilter(UuidRangeFilter::class, properties: ['id'])]
#[ApiFilter(OrderFilter::class, properties: ["id" => "DESC"])]
class SoManyUid
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid')]
    public Uuid $id;

    #[ORM\Column(nullable: true)]
    public $content;

    public function __construct($id)
    {
        if ($id) {
            $this->id = Uuid::fromString($id);
        } else {
            $this->id = Uuid::v7();
        }
    }
}
