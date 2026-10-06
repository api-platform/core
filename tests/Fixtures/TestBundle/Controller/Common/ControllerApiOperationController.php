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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Controller\Common;

use ApiPlatform\Metadata\ApiOperation;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\Checkout;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ControllerApiOperationController
{
    #[Route('/controller_api_operation/checkout', name: 'controller_api_operation_checkout', methods: ['POST'])]
    #[ApiOperation(new Post(input: CheckoutInput::class, output: CheckoutOutput::class))]
    public function __invoke(CheckoutInput $data): CheckoutOutput
    {
        $output = new CheckoutOutput();
        $output->reference = $data->reference;
        $output->status = 'confirmed';

        return $output;
    }

    #[Route('/controller_api_operation/checkouts/{id}', name: 'controller_api_operation_checkout_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[ApiOperation(new Get(class: Checkout::class, uriVariables: ['id'], formats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json'], 'csv' => ['text/csv']]))]
    public function get(int $id): Checkout
    {
        return Checkout::create($id) ?? throw new NotFoundHttpException();
    }

    /**
     * @return Checkout[]
     */
    #[Route('/controller_api_operation/checkouts', name: 'controller_api_operation_checkout_collection', methods: ['GET'])]
    #[ApiOperation(new GetCollection(class: Checkout::class, formats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json'], 'csv' => ['text/csv']]))]
    public function getCollection(): array
    {
        return [Checkout::create(1), Checkout::create(2)];
    }

    #[Route('/controller_api_operation/checkouts/{id}', name: 'controller_api_operation_checkout_patch', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[ApiOperation(new Patch(class: Checkout::class, uriVariables: ['id'], provider: [Checkout::class, 'provide'], inputFormats: ['json' => ['application/merge-patch+json']], outputFormats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]))]
    public function patch(Checkout $data): Checkout
    {
        $data->status = 'patched';

        return $data;
    }

    #[Route('/controller_api_operation/checkouts/{id}', name: 'controller_api_operation_checkout_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[ApiOperation(new Delete(class: Checkout::class, uriVariables: ['id'], provider: [Checkout::class, 'provide']))]
    public function delete(Checkout $data): null
    {
        return null;
    }
}
