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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * FooDummy.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query', paginationType: 'page')], order: ['dummy.name'])]
#[ODM\Document]
class FooDummy
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string The foo name
     */
    #[ODM\Field]
    private $name;
    /**
     * @var Dummy The foo dummy
     */
    #[ODM\ReferenceOne(targetDocument: Dummy::class, cascade: ['persist'], storeAs: 'id')]
    private ?Dummy $dummy = null;

    /**
     * @var Collection<SoMany>
     */
    #[ODM\ReferenceMany(targetDocument: SoMany::class, cascade: ['persist'], storeAs: 'id')]
    public Collection $soManies;

    public function __construct()
    {
        $this->soManies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDummy(): ?Dummy
    {
        return $this->dummy;
    }

    public function setDummy(Dummy $dummy): void
    {
        $this->dummy = $dummy;
    }
}
