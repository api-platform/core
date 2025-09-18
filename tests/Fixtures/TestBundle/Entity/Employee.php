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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Expression;

#[ApiResource]
#[Post]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/employees/{id}',
    uriVariables: [
        'companyId' => ['from_class' => Company::class, 'to_property' => 'company'],
        'id' => ['from_class' => Employee::class],
    ]
)]
#[Get]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/employees',
    uriVariables: [
        'companyId' => ['from_class' => Company::class, 'to_property' => 'company'],
    ],
    normalizationContext: ['groups' => ['company_employees_read']]
)]
#[GetCollection]
#[GetCollection(
    uriTemplate: '/companies-by-name/{name}/employees',
    uriVariables: [
        'name' => new Link(
            identifiers: ['name'],
            fromClass: Company::class,
            toProperty: 'company',
            security: 'company.name == "Test" or company.name == "NotTest"',
            extraProperties: ['uri_template' => '/company-by-name/{name}'],
            constraints: [
                new Expression(
                    'value.getName() == "Test"',
                ),
            ]
        ),
    ],
)]
#[ORM\Entity]
class Employee
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    /**
     * @var string The dummy name
     */
    #[ORM\Column]
    #[Groups(['company_employees_read'])]
    public string $name;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[Groups(['company_employees_read'])]
    public ?Company $company = null;

    public function getId()
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
