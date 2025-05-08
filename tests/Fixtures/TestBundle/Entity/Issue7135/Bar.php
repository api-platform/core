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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7135;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ApiResource(
    shortName: 'BarPr7135',
    operations: [
        new Get(
            uriTemplate: '/pull-request-7135/bar/{id}',
        ),
    ]
)]
#[ORM\Table(name: 'bar6466')]
class Bar
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public Uuid $id;

    #[ORM\Column]
    public string $title = '';

    public function __construct(?Uuid $id = null)
    {
        $this->id = $id ?: Uuid::v7();
    }
}
