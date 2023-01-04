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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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
#[ODM\Document]
class RelatedLinkedDummy
{
    #[ApiProperty(writable: false)]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;

    #[ODM\ReferenceOne(targetDocument: SecuredDummy::class, storeAs: 'id')]
    private SecuredDummy $securedDummy;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getSecuredDummy(): SecuredDummy
    {
        return $this->securedDummy;
    }

    public function setSecuredDummy(SecuredDummy $securedDummy): void
    {
        $this->securedDummy = $securedDummy;
    }
}
