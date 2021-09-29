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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UriVariable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource]
#[Get]
#[Post]
#[ApiResource('/employees/{employeeId}/company', openapiContext: ['parameters' => [
    ['description' => 'The id of the employee', 'name' => 'employeeId', 'in' => 'path', 'required' => true],
]])]
#[Get]
class Company
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     */
    public string $name;

    // TODO 3.0: Set the uri variable directly in the api resource and remove this property
    #[UriVariable(parameterName: 'employeeId', inverseProperty: 'company', property: null, targetClass: Employee::class)]
    public $employees; // only used to set metadata

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
