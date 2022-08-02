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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Circular Reference.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['circular']])]
#[ODM\Document]
class CircularReference
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[Groups(['circular'])]
    #[ODM\ReferenceOne(targetDocument: self::class, inversedBy: 'children')]
    public $parent;
    #[Groups(['circular'])]
    #[ODM\ReferenceMany(targetDocument: self::class, mappedBy: 'parent')]
    public Collection|iterable $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
