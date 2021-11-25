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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource]
#[Get]
#[Post]
#[ApiResource(
    uriTemplate: '/employees/{employeeId}/rooms/{roomId}/company/{companyId}',
    uriVariables: [
        'employeeId' => ['from_class' => Employee::class, 'from_property' => 'company'],
    ],
)]
#[Get]
#[ApiResource(
    uriTemplate: '/employees/{employeeId}/company',
    uriVariables: [
        'employeeId' => ['from_class' => Employee::class, 'from_property' => 'company'],
    ],
)]
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

    /** @var Employee[] */
    #[link(toProperty: 'company')]
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
