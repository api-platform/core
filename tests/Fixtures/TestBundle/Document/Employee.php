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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[Post]
#[ApiResource(uriTemplate: '/companies/{companyId}/employees/{id}', uriVariables: ['companyId' => ['from_class' => Company::class, 'to_property' => 'company'], 'id' => ['from_class' => Employee::class]])]
#[Get]
#[ApiResource(uriTemplate: '/companies/{companyId}/employees', uriVariables: ['companyId' => ['from_class' => Company::class, 'to_property' => 'company']], normalizationContext: ['groups' => ['company_employees_read']])]
#[GetCollection]
#[ODM\Document]
class Employee
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;

    #[ODM\Field]
    #[Groups(['company_employees_read'])]
    public ?string $name = null;

    #[ODM\ReferenceOne(targetDocument: Company::class, storeAs: 'id')]
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
