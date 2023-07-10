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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5587;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'issue5584_employee',
    operations: [
        new Post(uriTemplate: 'issue5584_employees'),
    ],
    normalizationContext: [
        'groups' => ['r'],
    ],
    denormalizationContext: [
        'groups' => ['w'],
    ],
)]
#[ORM\Table(name: 'issue5584_employee')]
#[ORM\Entity()]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['w', 'r'])]
    private $id;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['w', 'r'])]
    public $name;
    #[ORM\ManyToMany(targetEntity: Business::class, mappedBy: 'businessEmployees')]
    public $businesses;

    public function getId(): ?int
    {
        return $this->id;
    }
}
