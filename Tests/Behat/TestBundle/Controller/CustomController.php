<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Controller;

use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Custom Controller.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomController extends Controller
{
    /**
     * @return Response
     */
    public function customAction($id)
    {
        return new Response(sprintf('This is a custom action for %d.', $id));
    }
}
