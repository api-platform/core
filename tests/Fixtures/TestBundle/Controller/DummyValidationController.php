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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyValidation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DummyValidationController
{
    /**
     * @Route(
     *     name="post_validation_groups",
     *     path="/dummy_validation/validation_groups",
     *     defaults={"_api_resource_class"=DummyValidation::class, "_api_collection_operation_name"="post_validation_groups"}
     * )
     * @Method("POST")
     */
    public function postValidationGroups($data)
    {
        return $data;
    }

    /**
     * @Route(
     *     name="post_validation_sequence",
     *     path="/dummy_validation/validation_sequence",
     *     defaults={"_api_resource_class"=DummyValidation::class, "_api_collection_operation_name"="post_validation_sequence"}
     * )
     * @Method("POST")
     */
    public function postValidationSequence($data)
    {
        return $data;
    }
}
