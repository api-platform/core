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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

#[ApiResource()]
#[ApiResource(
    uriTemplate: '/secured_dummies/{securedDummyId}/to_from',
    operations: [new GetCollection()],
    uriVariables: [
        'securedDummyId' => new Link(toProperty: 'securedDummy', fromClass: SecuredDummy::class, security: "is_granted('ROLE_USER') and securedDummy.getOwner() == user"),
    ]
)]
#[ApiResource(
    uriTemplate: '/secured_dummies/{securedDummyId}/with_name',
    operations: [new GetCollection()],
    uriVariables: [
        'securedDummyId' => new Link(toProperty: 'securedDummy', fromClass: SecuredDummy::class, security: "is_granted('ROLE_USER') and testObj.getOwner() == user", securityObjectName: 'testObj'),
    ]
)]
#[ApiResource(
    uriTemplate: '/secured_dummies/{securedDummyId}/related/{id}',
    operations: [new GetCollection()],
    uriVariables: [
        'securedDummyId' => new Link(toProperty: 'securedDummy', fromClass: SecuredDummy::class, security: "is_granted('ROLE_USER') and securedDummy.getOwner() == user"),
        'id' => new Link(fromClass: RelatedLinkedDummy::class, security: "is_granted('ROLE_USER') and testObj.getSecuredDummy().getOwner() == user", securityObjectName: 'testObj'),
    ]
)]
#[Entity]
class RelatedLinkedDummy
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: SecuredDummy::class)]
    private ?SecuredDummy $securedDummy = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getSecuredDummy(): ?SecuredDummy
    {
        return $this->securedDummy;
    }

    public function setSecuredDummy(?SecuredDummy $securedDummy): void
    {
        $this->securedDummy = $securedDummy;
    }
}
