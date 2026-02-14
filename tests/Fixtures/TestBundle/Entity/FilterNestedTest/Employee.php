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

use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Doctrine\Orm\Filter\UuidFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Employee entity for testing nested filter support with IriFilter and UuidFilter.
 */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                // Test direct relation filtering (should work)
                'department' => new QueryParameter(filter: new IriFilter()),
                'departmentId' => new QueryParameter(filter: new UuidFilter(), property: 'department'),

                // Test nested relation filtering (currently broken, should be fixed)
                'departmentCompany' => new QueryParameter(filter: new IriFilter(), property: 'department.company'),
                'departmentCompanyId' => new QueryParameter(filter: new UuidFilter(), property: 'department.company'),
            ]
        ),
    ]
)]
class Employee
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Department $department;

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

    public function getDepartment(): Department
    {
        return $this->department;
    }

    public function setDepartment(Department $department): self
    {
        $this->department = $department;

        return $this;
    }
}
