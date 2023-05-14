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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @author Brian Fox <brian@brianfox.fr>
 */
#[ApiResource(normalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], denormalizationContext: ['groups' => ['default'], 'enable_max_depth' => true])]
#[ORM\Entity]
class MaxDepthDummy
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['default'])]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 30)]
    #[Groups(['default'])]
    public $name;

    #[ApiProperty(fetchEager: false)]
    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['persist'])]
    #[Groups(['default'])]
    #[MaxDepth(1)]
    public $child;

    public function getId()
    {
        return $this->id;
    }
}
