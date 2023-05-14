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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;

class InitializeInputProcessor
{
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $resourceObject = $context['previous_data'];
        $resourceObject->name = $data->name;

        return $resourceObject;
    }
}
