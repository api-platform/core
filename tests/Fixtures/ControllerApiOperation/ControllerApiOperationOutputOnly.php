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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use Symfony\Component\Routing\Attribute\Route;

class ControllerApiOperationOutputOnly
{
    #[Route('/output_only', name: 'output_only', methods: ['POST'])]
    #[ApiOperation(new Post(input: CheckoutInput::class, output: CheckoutOutput::class))]
    public function __invoke(): CheckoutOutput
    {
        return new CheckoutOutput();
    }
}
