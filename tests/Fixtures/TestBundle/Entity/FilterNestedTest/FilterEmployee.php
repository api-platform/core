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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterNestedTest;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Doctrine\Orm\Filter\UuidFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Uid\Uuid;

/**
 * Employee entity for testing nested filter support with IriFilter, UuidFilter and OrderFilter.
 */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            paginationItemsPerPage: 10,
            parameters: [
                'department' => new QueryParameter(filter: new IriFilter()),
                'departmentId' => new QueryParameter(filter: new UuidFilter(), property: 'department'),

                'departmentCompany' => new QueryParameter(filter: new IriFilter(), property: 'department.company'),
                'departmentCompanyId' => new QueryParameter(filter: new UuidFilter(), property: 'department.company'),

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
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $hireDate = null;

    #[ORM\ManyToOne(targetEntity: FilterDepartment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FilterDepartment $department;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
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
