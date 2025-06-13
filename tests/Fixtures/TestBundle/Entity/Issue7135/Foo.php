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
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ApiResource(
    shortName: 'FooPr7135',
    operations: [
        new Post(
            uriTemplate: '/pull-request-7135/foo/',
        ),
    ],
    normalizationContext: ['iri_only' => true],
)]
#[ORM\Table(name: 'foo6466')]
class Foo
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public Uuid $id;

    #[ORM\ManyToOne(targetEntity: Bar::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    public Bar $bar;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }
}
