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
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomInputDto;

final class CustomInputDtoProcessor implements ProcessorInterface
{
    public function __construct(private ProcessorInterface $decorated)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof CustomInputDto) {
            throw new \InvalidArgumentException();
        }

        /**
         * @var \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoCustom
         */
        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        $resourceObject->lorem = $data->foo;
        $resourceObject->ipsum = (string) $data->bar;

        return $this->decorated->process($resourceObject, $operation, $uriVariables, $context);
    }
}
