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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/** *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"default"}, "enable_max_depth"=true},
 *     "denormalization_context"={"groups"={"default"}, "enable_max_depth"=true}
 * })
 * @ODM\Document
 *
 * @author Brian Fox <brian@brianfox.fr>
 */
class MaxDepthEagerDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     * @Groups({"default"})
     */
    private $id;

    /**
     * @ODM\Field(name="name", type="string")
     * @Groups({"default"})
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=MaxDepthEagerDummy::class, cascade={"persist"})
     * @Groups({"default"})
     * @MaxDepth(1)
     */
    public $child;

    public function getId()
    {
        return $this->id;
    }
}
