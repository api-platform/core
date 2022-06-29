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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RPCOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\State\RPCProcessor;

/**
 * RPC-like resource.
 */
#[ApiResource(operations: [new Post(status: 202, messenger: true, uriTemplate: 'rpc', output: false), new Post(status: 200, uriTemplate: 'rpc_output', output: RPCOutput::class, processor: RPCProcessor::class)])]
class RPC
{
    /**
     * @var string
     */
    public $value;
}
