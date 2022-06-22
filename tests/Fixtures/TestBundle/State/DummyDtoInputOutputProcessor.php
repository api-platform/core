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
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;

final class DummyDtoInputOutputProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     *
     * @param InputDto $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $outputDto = $context['previous_data'] ?? new OutputDto();
        if (!$outputDto->id) {
            $outputDto->id = 1;
        }

        $outputDto->baz = $data->bar;
        $outputDto->bat = $data->foo;

        return $outputDto;
    }
}
