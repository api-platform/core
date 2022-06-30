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
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * FooDummy.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(name: 'collection_query', paginationType: 'page')], order: ['dummy.name'])]
#[ORM\Entity]
class FooDummy
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var string The foo name
     */
    #[ORM\Column]
    private $name;
    /**
     * @var Dummy|null The foo dummy
     */
    #[ORM\ManyToOne(targetEntity: Dummy::class, cascade: ['persist'])]
    private ?Dummy $dummy = null;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDummy()
    {
        return $this->dummy;
    }

    public function setDummy(Dummy $dummy)
    {
        $this->dummy = $dummy;
    }
}
