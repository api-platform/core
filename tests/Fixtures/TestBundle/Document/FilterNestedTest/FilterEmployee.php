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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\FilterNestedTest;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Doctrine\Odm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ODM\Document]
#[ApiResource(
    operations: [
        new GetCollection(
            paginationItemsPerPage: 10,
            parameters: [
                'department' => new QueryParameter(filter: new IriFilter()),

                'departmentCompany' => new QueryParameter(filter: new IriFilter(), property: 'department.company'),

                'orderDepartmentName' => new QueryParameter(filter: new SortFilter(), property: 'department.name', nativeType: new BuiltinType(TypeIdentifier::STRING)),
                'orderName' => new QueryParameter(filter: new SortFilter(), property: 'name', nativeType: new BuiltinType(TypeIdentifier::STRING)),
                'orderHireDate' => new QueryParameter(filter: new SortFilter(nullsComparison: OrderFilterInterface::NULLS_ALWAYS_FIRST), property: 'hireDate', nativeType: new BuiltinType(TypeIdentifier::STRING)),
                'orderHireDateNullsLast' => new QueryParameter(filter: new SortFilter(nullsComparison: OrderFilterInterface::NULLS_ALWAYS_LAST), property: 'hireDate', nativeType: new BuiltinType(TypeIdentifier::STRING)),
                'orderCompanyName' => new QueryParameter(filter: new SortFilter(), property: 'department.company.name', nativeType: new BuiltinType(TypeIdentifier::STRING)),
            ]
        ),
    ]
)]
class FilterEmployee
{
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $hireDate = null;

    #[ODM\ReferenceOne(targetDocument: FilterDepartment::class, storeAs: 'id')]
    private FilterDepartment $department;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getHireDate(): ?\DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(?\DateTimeImmutable $hireDate): self
    {
        $this->hireDate = $hireDate;

        return $this;
    }

    public function getDepartment(): FilterDepartment
    {
        return $this->department;
    }

    public function setDepartment(FilterDepartment $department): self
    {
        $this->department = $department;

        return $this;
    }
}
