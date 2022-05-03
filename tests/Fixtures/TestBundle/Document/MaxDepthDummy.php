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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * *
 *
 * @ODM\Document
 *
 * @author Brian Fox <brian@brianfox.fr>
 */
#[ApiResource(normalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], denormalizationContext: ['groups' => ['default'], 'enable_max_depth' => true])]
class MaxDepthDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     * @Groups({"default"})
     */
    private $id;
    /**
     * @ODM\Field(name="name", type="string")
     * @Groups({"default"})
     */
    public $name;
    /**
     * @ODM\ReferenceOne(targetDocument=MaxDepthDummy::class, cascade={"persist"})
     * @Groups({"default"})
     * @MaxDepth(1)
     */
    public $child;

    public function getId()
    {
        return $this->id;
    }
}
