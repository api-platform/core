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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secured resource with legacy access_control attribute.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_USER\') and object.getOwner() == user'), new Put(security: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() == user', extraProperties: ['standard_put' => false]), new GetCollection(), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], graphQlOperations: [new Query(name: 'item_query', security: 'is_granted(\'ROLE_USER\') and object.getOwner() == user'), new Mutation(name: 'delete'), new Mutation(name: 'update', security: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() == user'), new Mutation(name: 'create', security: 'is_granted(\'ROLE_ADMIN\')', securityMessage: 'Only admins can create a secured dummy.')], security: 'is_granted(\'ROLE_USER\')')]
#[ORM\Entity]
class LegacySecuredDummy
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var string The title
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $title;
    /**
     * @var string The description
     */
    #[ORM\Column]
    private string $description = '';
    /**
     * @var string The owner
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }
}
