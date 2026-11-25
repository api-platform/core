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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Doctrine\Odm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ODM\Document]
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
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string', nullable: true)]
    public ?string $name = null;

    #[ODM\ReferenceOne(targetDocument: self::class, storeAs: 'id', nullable: true)]
    public ?self $manager = null;

    #[ODM\ReferenceOne(targetDocument: TreeDepartment::class, storeAs: 'id', nullable: true)]
    public ?TreeDepartment $department = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
