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
 * @author Brian Fox <brian@brianfox.fr>
 */
#[ApiResource(normalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], denormalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], graphQlOperations: [])]
#[ODM\Document]
class MaxDepthDummy
{
    #[Groups(['default'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[Groups(['default'])]
    #[ODM\Field(name: 'name', type: 'string')]
    public $name;
    #[Groups(['default'])]
    #[MaxDepth(1)]
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: ['persist'])]
    public $child;

    public function getId()
    {
        return $this->id;
    }
}
