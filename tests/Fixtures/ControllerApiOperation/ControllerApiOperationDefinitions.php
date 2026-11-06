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

namespace ApiPlatform\Tests\Fixtures\ControllerApiOperation;

use ApiPlatform\Metadata\ApiOperation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use Symfony\Component\Routing\Attribute\Route;

class ControllerApiOperationDefinitions
{
    #[Route('/with_uri_template', name: 'with_uri_template', methods: ['POST'])]
    #[ApiOperation(new Post(uriTemplate: '/other', output: CheckoutOutput::class))]
    public function withUriTemplate(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[Route('/unnamed', methods: ['POST'])]
    #[ApiOperation(new Post(output: CheckoutOutput::class))]
    public function withUnnamedRoute(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[ApiOperation(new Post(routeName: 'inner_route_name', output: CheckoutOutput::class))]
    public function withInnerRouteName(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[Route('/without_resource_class', name: 'without_resource_class', methods: ['POST'])]
    #[ApiOperation(new Post())]
    public function withoutResourceClass(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[Route('/get', name: 'get_route', methods: ['GET'])]
    #[ApiOperation(new Get(class: CheckoutOutput::class))]
    public function withGet(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[Route('/get_collection', name: 'get_collection_route', methods: ['GET'])]
    #[ApiOperation(new GetCollection(class: CheckoutOutput::class))]
    public function withGetCollection(): array
    {
        return [];
    }

    #[Route('/get_explicit_read', name: 'get_explicit_read_route', methods: ['GET'])]
    #[ApiOperation(new Get(class: CheckoutOutput::class, read: true))]
    public function withExplicitRead(): CheckoutOutput
    {
        return new CheckoutOutput();
    }

    #[Route('/patch', name: 'patch_route', methods: ['PATCH'])]
    #[ApiOperation(new Patch(class: CheckoutOutput::class))]
    public function withPatch(): CheckoutOutput
    {
        return new CheckoutOutput();
    }
}
