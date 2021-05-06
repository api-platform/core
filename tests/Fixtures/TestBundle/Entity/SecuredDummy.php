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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secured resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"},
 *         "get_from_data_provider_generator"={
 *             "method"="GET",
 *             "path"="custom_data_provider_generator",
 *             "security"="is_granted('ROLE_USER')"
 *         },
 *         "post"={"security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_USER') and object.getOwner() == user"},
 *         "put"={"security_post_denormalize"="is_granted('ROLE_USER') and previous_object.getOwner() == user"},
 *     },
 *     graphql={
 *         "item_query"={"security"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getOwner() == user)"},
 *         "collection_query"={"security"="is_granted('ROLE_ADMIN')"},
 *         "delete"={},
 *         "update"={"security_post_denormalize"="is_granted('ROLE_USER') and previous_object.getOwner() == user"},
 *         "create"={"security"="is_granted('ROLE_ADMIN')", "security_message"="Only admins can create a secured dummy."}
 *     }
 * )
 * @ORM\Entity
 */
class SecuredDummy
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The title
     *
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string The description
     *
     * @ORM\Column
     */
    private $description = '';

    /**
     * @var string The dummy secret property, only readable/writable by specific users
     *
     * @ORM\Column
     * @ApiProperty(security="is_granted('ROLE_ADMIN')")
     */
    private $adminOnlyProperty = '';

    /**
     * @var string Secret property, only readable/writable by owners
     *
     * @ORM\Column
     * @ApiProperty(
     *     security="object == null or object.getOwner() == user",
     *     securityPostDenormalize="object.getOwner() == user",
     * )
     */
    private $ownerOnlyProperty = '';

    /**
     * @var string The owner
     *
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $owner;

    /**
     * A collection of dummies that only admins can access.
     *
     * @var Collection<RelatedDummy> Several dummies
     *
     * @ORM\ManyToMany(targetEntity="RelatedDummy")
     * @ORM\JoinTable(name="secured_dummy_related_dummy")
     * @ApiProperty(security="is_granted('ROLE_ADMIN')")
     */
    public $relatedDummies;

    /**
     * A dummy that only admins can access.
     *
     * @var RelatedDummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     * @ORM\JoinColumn(name="related_dummy_id")
     * @ApiProperty(security="is_granted('ROLE_ADMIN')")
     */
    protected $relatedDummy;

    /**
     * A collection of dummies that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     *
     * @var Collection<RelatedSecuredDummy> Several dummies
     *
     * @ORM\ManyToMany(targetEntity="RelatedSecuredDummy")
     * @ORM\JoinTable(name="secured_dummy_related_secured_dummy")
     * @ApiProperty(security="is_granted('ROLE_USER')")
     */
    public $relatedSecuredDummies;

    /**
     * A dummy that only users can access. The security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedSecuredDummy")
     * @ORM\JoinColumn(name="related_secured_dummy_id")
     * @ApiProperty(security="is_granted('ROLE_USER')")
     */
    protected $relatedSecuredDummy;

    /**
     * Collection of dummies that anyone can access. There is no @ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     *
     * @var Collection<RelatedSecuredDummy> Several dummies
     *
     * @ORM\ManyToMany(targetEntity="RelatedSecuredDummy")
     * @ORM\JoinTable(name="secured_dummy_public_related_secured_dummy")
     */
    public $publicRelatedSecuredDummies;

    /**
     * A dummy that anyone can access. There is no @ApiProperty security, and the security on RelatedSecuredDummy shouldn't be run.
     *
     * @var RelatedSecuredDummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedSecuredDummy")
     * @ORM\JoinColumn(name="public_related_secured_dummy_id")
     */
    protected $publicRelatedSecuredDummy;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
        $this->relatedSecuredDummies = new ArrayCollection();
        $this->publicRelatedSecuredDummies = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
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

    public function getOwner(): string
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
