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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secured resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_USER\') and object.getOwner() == user'), new Put(securityPostDenormalize: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() == user'), new GetCollection(security: 'is_granted(\'ROLE_USER\') or is_granted(\'ROLE_ADMIN\')'), new GetCollection(uriTemplate: 'custom_data_provider_generator', security: 'is_granted(\'ROLE_USER\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], graphQlOperations: [new Query(name: 'item_query', security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_USER\') and object.getOwner() == user)'), new QueryCollection(name: 'collection_query', security: 'is_granted(\'ROLE_ADMIN\')'), new Mutation(name: 'delete'), new Mutation(name: 'update', securityPostDenormalize: 'is_granted(\'ROLE_USER\') and previous_object.getOwner() ==  user'), new Mutation(name: 'create', security: 'is_granted(\'ROLE_ADMIN\')', securityMessage: 'Only admins can create a secured dummy.')], security: 'is_granted(\'ROLE_USER\')')]
#[ODM\Document]
class SecuredDummy
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @var string|null The title
     */
    #[Assert\NotBlank]
    #[ODM\Field]
    private ?string $title = null;

    /**
     * @var string The description
     */
    #[ODM\Field]
    private string $description = '';

    /**
     * @var string The dummy secret property, only readable/writable by specific users
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ODM\Field]
    private ?string $adminOnlyProperty = '';

    /**
     * @var string Secret property, only readable/writable by owners
     */
    #[ApiProperty(security: 'object == null or object.getOwner() == user', securityPostDenormalize: 'object.getOwner() == user')]
    #[ODM\Field]
    private ?string $ownerOnlyProperty = '';

    /**
     * @var string|null The owner
     */
    #[Assert\NotBlank]
    #[ODM\Field]
    private ?string $owner = null;

    /**
     * @var Collection<RelatedDummy> Several dummies
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ODM\ReferenceMany(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public $relatedDummies;

    /**
     * @var RelatedDummy
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    protected $relatedDummy;

    /**
     * A collection of dummies that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     *
     * @var Collection<RelatedSecuredDummy> Several dummies
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    #[ODM\ReferenceMany(targetDocument: RelatedSecuredDummy::class, storeAs: 'id', nullable: true)]
    public $relatedSecuredDummies;

    /**
     * A dummy that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    #[ODM\ReferenceOne(targetDocument: RelatedSecuredDummy::class, storeAs: 'id', nullable: true)]
    protected $relatedSecuredDummy;
    /**
     * Collection of dummies that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     *
     * @var Collection<RelatedSecuredDummy> Several dummies
     */
    #[ODM\ReferenceMany(targetDocument: RelatedSecuredDummy::class, storeAs: 'id', nullable: true)]
    public $publicRelatedSecuredDummies;
    /**
     * A dummy that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy
     */
    #[ODM\ReferenceOne(targetDocument: RelatedSecuredDummy::class, storeAs: 'id', nullable: true)]
    protected $publicRelatedSecuredDummy;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
        $this->relatedSecuredDummies = new ArrayCollection();
        $this->publicRelatedSecuredDummies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getAdminOnlyProperty(): ?string
    {
        return $this->adminOnlyProperty;
    }

    public function setAdminOnlyProperty(?string $adminOnlyProperty)
    {
        $this->adminOnlyProperty = $adminOnlyProperty;
    }

    public function getOwnerOnlyProperty(): ?string
    {
        return $this->ownerOnlyProperty;
    }

    public function setOwnerOnlyProperty(?string $ownerOnlyProperty)
    {
        $this->ownerOnlyProperty = $ownerOnlyProperty;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setOwner(string $owner)
    {
        $this->owner = $owner;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummies->add($relatedDummy);
    }

    public function getRelatedDummies()
    {
        return $this->relatedDummies;
    }

    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedSecuredDummy(RelatedSecuredDummy $relatedSecuredDummy)
    {
        $this->relatedSecuredDummies->add($relatedSecuredDummy);
    }

    public function getRelatedSecuredDummies()
    {
        return $this->relatedSecuredDummies;
    }

    public function getRelatedSecuredDummy()
    {
        return $this->relatedSecuredDummy;
    }

    public function setRelatedSecuredDummy(RelatedSecuredDummy $relatedSecuredDummy)
    {
        $this->relatedSecuredDummy = $relatedSecuredDummy;
    }

    public function addPublicRelatedSecuredDummy(RelatedSecuredDummy $publicRelatedSecuredDummy)
    {
        $this->publicRelatedSecuredDummies->add($publicRelatedSecuredDummy);
    }

    public function getPublicRelatedSecuredDummies()
    {
        return $this->publicRelatedSecuredDummies;
    }

    public function getPublicRelatedSecuredDummy()
    {
        return $this->publicRelatedSecuredDummy;
    }

    public function setPublicRelatedSecuredDummy(RelatedSecuredDummy $publicRelatedSecuredDummy)
    {
        $this->publicRelatedSecuredDummy = $publicRelatedSecuredDummy;
    }
}
