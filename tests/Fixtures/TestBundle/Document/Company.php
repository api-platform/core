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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ODM\Document
 */
#[ApiResource]
#[GetCollection]
#[Get]
#[Post]
#[ApiResource(uriTemplate: '/employees/{employeeId}/rooms/{roomId}/company/{companyId}', uriVariables: ['employeeId' => ['from_class' => Employee::class, 'from_property' => 'company']])]
#[Get]
#[ApiResource(uriTemplate: '/employees/{employeeId}/company', uriVariables: ['employeeId' => ['from_class' => Employee::class, 'from_property' => 'company']])]
class Company
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field
     */
    #[Groups(['company_employees_read'])]
    public $name;

    /** @var Employee[] */
    #[Link(toProperty: 'company')]
    public $employees = []; // only used to set metadata

    public function getId()
    {
        return $this->id;
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
