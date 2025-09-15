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

namespace ApiPlatform\Hal\Tests\Fixtures;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @author Brian Fox <brian@brianfox.fr>
 */
#[ApiResource(normalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], denormalizationContext: ['groups' => ['default'], 'enable_max_depth' => true], graphQlOperations: [])]
class MaxDepthDummy
{
    #[Groups(['default'])]
    public $id;

    #[Groups(['default'])]
    public $name;

    #[ApiProperty(fetchEager: false)]
    #[Groups(['default'])]
    #[MaxDepth(1)]
    public $child;
}
