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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomActionController extends Controller
{
    /**
     * @Route(
     *     name="custom_normalization",
     *     path="/custom/{id}/normalization",
     *     defaults={"_api_resource_class"=CustomActionDummy::class, "_api_item_operation_name"="custom_normalization"}
     * )
     * @Method("GET")
     */
    public function customNormalizationAction(CustomActionDummy $_data)
    {
        $_data->setFoo('foo');

        return $this->json($_data);
    }

    /**
     * @Route(
     *     name="custom_denormalization",
     *     path="/custom/denormalization",
     *     defaults={
     *         "_api_resource_class"=CustomActionDummy::class,
     *         "_api_collection_operation_name"="custom_denormalization",
     *         "_api_receive"=false
     *     }
     * )
     * @Method("POST")
     */
    public function customDenormalizationAction(Request $request)
    {
        if ($request->attributes->has('data')) {
            throw new \RuntimeException('The "data" attribute must not be set.');
        }

        $object = new CustomActionDummy();
        $object->setFoo('custom!');

        return $object;
    }

    /**
     * @Route(
     *     name="short_custom_normalization",
     *     path="/short_custom/{id}/normalization",
     *     defaults={"_api_resource_class"=CustomActionDummy::class, "_api_item_operation_name"="custom_normalization"}
     * )
     * @Method("GET")
     */
    public function shortCustomNormalizationAction(CustomActionDummy $data)
    {
        $data->setFoo('short');

        return $this->json($data);
    }

    /**
     * @Route(
     *     name="short_custom_denormalization",
     *     path="/short_custom/denormalization",
     *     defaults={
     *         "_api_resource_class"=CustomActionDummy::class,
     *         "_api_collection_operation_name"="custom_denormalization",
     *         "_api_receive"=false
     *     }
     * )
     * @Method("POST")
     */
    public function shortCustomDenormalizationAction(Request $request)
    {
        if ($request->attributes->has('data')) {
            throw new \RuntimeException('The "data" attribute must not be set.');
        }

        $object = new CustomActionDummy();
        $object->setFoo('short declaration');

        return $object;
    }
}
