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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UriVariable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource]
#[Post]
#[ApiResource('/companies/{companyId}/employees/{id}')]
#[Get]
#[ApiResource('/companies/{companyId}/employees')]
#[GetCollection]
class Employee
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     */
    public string $name;

    /**
     * @ORM\ManyToOne(targetEntity="ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company")
     */
    #[UriVariable('companyId')]
    public Company $company;

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
