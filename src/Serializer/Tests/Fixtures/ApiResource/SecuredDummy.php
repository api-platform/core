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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secured resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(operations: [
    new Get(security: 'is_granted(\'ROLE_USER\') and object.getOwner() == user'),
    new Put(securityPostDenormalize: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() == user', extraProperties: ['standard_put' => false]),
    new GetCollection(security: 'is_granted(\'ROLE_USER\') or is_granted(\'ROLE_ADMIN\')'),
    new GetCollection(uriTemplate: 'custom_data_provider_generator', security: 'is_granted(\'ROLE_USER\')'),
    new Post(security: 'is_granted(\'ROLE_ADMIN\')'),
],
    graphQlOperations: [
        new Query(name: 'item_query', security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_USER\') and object.getOwner() == user)'),
        new QueryCollection(name: 'collection_query', security: 'is_granted(\'ROLE_ADMIN\')'),
        new Mutation(name: 'delete'),
        new Mutation(name: 'update', securityPostDenormalize: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() == user'),
        new Mutation(name: 'create', security: 'is_granted(\'ROLE_ADMIN\')', securityMessage: 'Only admins can create a secured dummy.'),
    ],
    security: 'is_granted(\'ROLE_USER\')'
)]
class SecuredDummy
{
    private ?int $id = null;

    /**
     * @var string The title
     */
    #[Assert\NotBlank]
    private string $title;

    /**
     * @var string The description
     */
    private string $description = '';

    /**
     * @var string The dummy secret property, only readable/writable by specific users
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    private string $adminOnlyProperty = '';

    /**
     * @var string Secret property, only readable/writable by owners
     */
    #[ApiProperty(security: 'object == null or object.getOwner() == user', securityPostDenormalize: 'object.getOwner() == user')]
    private string $ownerOnlyProperty = '';

    /**
     * @var string The owner
     */
    #[Assert\NotBlank]
    private string $owner;

    /**
     * A collection of dummies that only admins can access.
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    public Collection|iterable $relatedDummies;

    /**
     * A dummy that only admins can access.
     *
     * @var RelatedDummy|null
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    protected $relatedDummy;

    /**
     * A collection of dummies that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    public Collection|iterable $relatedSecuredDummies;

    /**
     * A dummy that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    protected $relatedSecuredDummy;

    /**
     * Collection of dummies that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     */
    public iterable $publicRelatedSecuredDummies;

    /**
     * A dummy that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     */
    protected $publicRelatedSecuredDummy;

    public function __construct()
    {
        $this->relatedDummies = [];
        $this->relatedSecuredDummies = [];
        $this->publicRelatedSecuredDummies = [];
    }

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

    public function getAdminOnlyProperty(): ?string
    {
        return $this->adminOnlyProperty;
    }

    public function setAdminOnlyProperty(?string $adminOnlyProperty): void
    {
        $this->adminOnlyProperty = $adminOnlyProperty;
    }

    public function getOwnerOnlyProperty(): ?string
    {
        return $this->ownerOnlyProperty;
    }

    public function setOwnerOnlyProperty(?string $ownerOnlyProperty): void
    {
        $this->ownerOnlyProperty = $ownerOnlyProperty;
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
