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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\RPCOutput;

/**
 * RPC-like resource.
 *
 * @ApiResource(
 *     itemOperations={},
 *     collectionOperations={
 *         "post"={"status"=202, "messenger"=true, "path"="rpc", "output"=false},
 *         "post_output"={"method"="POST", "status"=200, "path"="rpc_output", "output"=RPCOutput::class}
 *     },
 * )
 */
class RPC
{
    /**
     * @var string
     */
    public $value;
}
