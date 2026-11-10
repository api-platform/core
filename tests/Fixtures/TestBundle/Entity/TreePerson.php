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

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'hasManagerOfManager' => new QueryParameter(
                    filter: new ExistsFilter(),
                    property: 'manager.manager.name',
                    nativeType: new BuiltinType(TypeIdentifier::BOOL),
                ),
                'hasDepartmentPart' => new QueryParameter(
                    filter: new ExistsFilter(),
                    property: 'department.part.name',
                    nativeType: new BuiltinType(TypeIdentifier::BOOL),
                ),
            ],
        ),
        new Get(),
    ]
)]
class TreePerson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    public ?self $manager = null;

    #[ORM\ManyToOne(targetEntity: TreeDepartment::class)]
    public ?TreeDepartment $department = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
