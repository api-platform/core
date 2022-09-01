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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\DummyCollectionDtoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\DummyIdCollectionDtoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\DummyFooCollectionDto;

class DummyCollectionDtoProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $class = $operation->getOutput()['class'];
        switch ($class) {
            case DummyCollectionDtoOutput::class:
            case DummyFooCollectionDto::class:
                return [
                    new $class('lorem', 1),
                    new $class('ipsum', 2),
                ];
            case DummyIdCollectionDtoOutput::class:
                return [
                    new $class(1, 'lorem', 1),
                    new $class(2, 'ipsum', 2),
                ];
            default:
                return [];
        }
    }
}
