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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Controller\Issue6355;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6355\OrderDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6355\OrderProductCount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateOrderProductCountController extends AbstractController
{
    public function __invoke(OrderProductCount $data, Request $request): OrderDto
    {
        $dto = new OrderDto();
        $dto->id = 1;

        return $dto;
    }
}
