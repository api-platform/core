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

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
#[ApiResource(uriTemplate: '/absolute_url_relation_dummies/{id}/absolute_url_dummies{._format}', uriVariables: ['id' => new Link(fromClass: AbsoluteUrlRelationDummy::class, identifiers: ['id'], toProperty: 'absoluteUrlRelationDummy')], status: 200, urlGenerationStrategy: UrlGeneratorInterface::ABS_URL, operations: [new GetCollection()])]
#[ORM\Entity]
class AbsoluteUrlDummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: AbsoluteUrlRelationDummy::class, inversedBy: 'absoluteUrlDummies')]
    public $absoluteUrlRelationDummy;

    public function getId()
    {
        return $this->id;
    }
}
