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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\Orm;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomActionController extends AbstractController
{
    /**
     * @Route(
     *     methods={"GET"},
     *     name="custom_normalization",
     *     path="/custom/{id}/normalization",
     *     defaults={"_api_resource_class"=CustomActionDummy::class, "_api_item_operation_name"="custom_normalization"}
     * )
     */
    public function customNormalizationAction(CustomActionDummy $data)
    {
        $data->setFoo('foo');

        return $this->json($data);
    }

    /**
     * @Route(
     *     methods={"POST"},
     *     name="custom_denormalization",
     *     path="/custom/denormalization",
     *     defaults={
     *         "_api_resource_class"=CustomActionDummy::class,
     *         "_api_collection_operation_name"="custom_denormalization",
     *         "_api_receive"=false
     *     }
     * )
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
     *     methods={"GET"},
     *     name="short_custom_normalization",
     *     path="/short_custom/{id}/normalization",
     *     defaults={"_api_resource_class"=CustomActionDummy::class, "_api_item_operation_name"="custom_normalization"}
     * )
     */
    public function shortCustomNormalizationAction(CustomActionDummy $data)
    {
        $data->setFoo('short');

        return $this->json($data);
    }

    /**
     * @Route(
     *     methods={"POST"},
     *     name="short_custom_denormalization",
     *     path="/short_custom/denormalization",
     *     defaults={
     *         "_api_resource_class"=CustomActionDummy::class,
     *         "_api_collection_operation_name"="custom_denormalization",
     *         "_api_receive"=false
     *     }
     * )
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
