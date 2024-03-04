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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Security\SecuredDummyAttributeBasedVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
#[ApiResource(
    uriTemplate: '/related_linked_dummies/{relatedDummyId}/from_from',
    operations: [new GetCollection()],
    uriVariables: [
        'relatedDummyId' => new Link(fromProperty: 'securedDummy', fromClass: RelatedLinkedDummy::class, security: "is_granted('ROLE_USER') and relatedDummy.getSecuredDummy().getOwner() == user", securityObjectName: 'relatedDummy'),
    ]
)]
#[ORM\Entity]
class SecuredDummy
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
     * @var string The dummy secret property, only readable/writable by specific users
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\Column]
    private string $adminOnlyProperty = '';

    /**
     * @var string Secret property, only readable/writable by owners
     */
    #[ApiProperty(security: 'object == null or object.getOwner() == user', securityPostDenormalize: 'object.getOwner() == user')]
    #[ORM\Column]
    private string $ownerOnlyProperty = '';

    /**
     * @var string Secret property, only readable/writable through voters using "property" attribute
     */
    #[ApiProperty(security: 'is_granted("'.SecuredDummyAttributeBasedVoter::ROLE.'", property)', securityPostDenormalize: 'is_granted("'.SecuredDummyAttributeBasedVoter::ROLE.'", property)')]
    #[ORM\Column]
    private string $attributeBasedProperty = '';

    /**
     * @var string The owner
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $owner;

    /**
     * A collection of dummies that only admins can access.
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\ManyToMany(targetEntity: RelatedDummy::class)]
    #[ORM\JoinTable(name: 'secured_dummy_related_dummy')]
    public Collection|iterable $relatedDummies;

    /**
     * A dummy that only admins can access.
     *
     * @var RelatedDummy|null
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[ORM\ManyToOne(targetEntity: RelatedDummy::class)]
    #[ORM\JoinColumn(name: 'related_dummy_id')]
    protected $relatedDummy;

    /**
     * A collection of dummies that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    #[ORM\ManyToMany(targetEntity: RelatedSecuredDummy::class)]
    #[ORM\JoinTable(name: 'secured_dummy_related_secured_dummy')]
    public Collection|iterable $relatedSecuredDummies;

    /**
     * A dummy that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy|null
     */
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    #[ORM\ManyToOne(targetEntity: RelatedSecuredDummy::class)]
    #[ORM\JoinColumn(name: 'related_secured_dummy_id')]
    protected $relatedSecuredDummy;

    /**
     * Collection of dummies that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     */
    #[ORM\ManyToMany(targetEntity: RelatedSecuredDummy::class)]
    #[ORM\JoinTable(name: 'secured_dummy_public_related_secured_dummy')]
    public Collection|iterable $publicRelatedSecuredDummies;

    /**
     * A dummy that anyone can access. There is no ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy|null
     */
    #[ORM\ManyToOne(targetEntity: RelatedSecuredDummy::class)]
    #[ORM\JoinColumn(name: 'public_related_secured_dummy_id')]
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

    public function getAttributeBasedProperty(): string
    {
        return $this->attributeBasedProperty;
    }

    public function setAttributeBasedProperty(string $attributeBasedProperty): void
    {
        $this->attributeBasedProperty = $attributeBasedProperty;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummies->add($relatedDummy);
    }

    public function getRelatedDummies(): Collection|iterable
    {
        return $this->relatedDummies;
    }

    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedSecuredDummy(RelatedSecuredDummy $relatedSecuredDummy): void
    {
        $this->relatedSecuredDummies->add($relatedSecuredDummy);
    }

    public function getRelatedSecuredDummies(): Collection|iterable
    {
        return $this->relatedSecuredDummies;
    }

    public function getRelatedSecuredDummy()
    {
        return $this->relatedSecuredDummy;
    }

    public function setRelatedSecuredDummy(RelatedSecuredDummy $relatedSecuredDummy): void
    {
        $this->relatedSecuredDummy = $relatedSecuredDummy;
    }

    public function addPublicRelatedSecuredDummy(RelatedSecuredDummy $publicRelatedSecuredDummy): void
    {
        $this->publicRelatedSecuredDummies->add($publicRelatedSecuredDummy);
    }

    public function getPublicRelatedSecuredDummies(): Collection|iterable
    {
        return $this->publicRelatedSecuredDummies;
    }

    public function getPublicRelatedSecuredDummy()
    {
        return $this->publicRelatedSecuredDummy;
    }

    public function setPublicRelatedSecuredDummy(RelatedSecuredDummy $publicRelatedSecuredDummy): void
    {
        $this->publicRelatedSecuredDummy = $publicRelatedSecuredDummy;
    }
}
