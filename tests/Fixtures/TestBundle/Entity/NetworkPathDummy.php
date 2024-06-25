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
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::NET_PATH)]
#[ApiResource(uriTemplate: '/network_path_relation_dummies/{id}/network_path_dummies{._format}', uriVariables: ['id' => new Link(fromClass: NetworkPathRelationDummy::class, identifiers: ['id'], toProperty: 'networkPathRelationDummy')], status: 200, urlGenerationStrategy: UrlGeneratorInterface::NET_PATH, operations: [new GetCollection()])]
#[ORM\Entity]
class NetworkPathDummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: NetworkPathRelationDummy::class, inversedBy: 'networkPathDummies')]
    public $networkPathRelationDummy;

    public function getId()
    {
        return $this->id;
    }
}
