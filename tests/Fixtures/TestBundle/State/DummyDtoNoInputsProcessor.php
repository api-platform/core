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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;

class DummyDtoNoInputsProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $decorated)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $object = $this->decorated->process($data, $operation, $uriVariables, $context);

        $output = $object instanceof DummyDtoNoInput ? new OutputDto() : new OutputDtoDocument();
        $output->id = $object->getId();
        $output->bat = (string) $object->lorem;
        $output->baz = (float) $object->ipsum;

        return $output;
    }
}
