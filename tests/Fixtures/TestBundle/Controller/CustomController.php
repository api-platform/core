<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Tests\Fixtures\TestBundle\Controller;

use ApiPlatform\Builder\JsonLd\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Custom Controller.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomController extends Controller
{
    /**
     * @return \ApiPlatform\Builder\JsonLd\Response
     */
    public function customAction($id)
    {
        return new Response(sprintf('This is a custom action for %d.', $id));
    }
}
