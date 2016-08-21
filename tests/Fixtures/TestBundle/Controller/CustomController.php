<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Custom Controller.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomController extends Controller
{
    public function customAction(int $id) : JsonResponse
    {
        return new JsonResponse(sprintf('This is a custom action for %d.', $id), 200, ['Content-Type' => 'application/ld+json; charset=utf-8']);
    }
}
