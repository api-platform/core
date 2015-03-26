<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Tests\Behat\TestBundle\Controller;

use Dunglas\JsonLdApiBundle\Controller\ResourceController;
use Dunglas\JsonLdApiBundle\Response\JsonLdResponse;

/**
 * Custom Controller.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomController extends ResourceController
{
    /**
     * @return JsonLdResponse
     */
    public function customAction($id)
    {
        return new JsonLdResponse(sprintf('This is a custom action for %d.', $id));
    }
}
