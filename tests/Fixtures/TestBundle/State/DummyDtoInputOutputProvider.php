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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoInputOutput as DummyDtoInputOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;
use Doctrine\Common\Collections\Collection;

final class DummyDtoInputOutputProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): OutputDto|OutputDtoDocument
    {
        /** @var DummyDtoInputOutput */
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        $outputDto = DummyDtoInputOutputDocument::class === $operation->getClass() ? new OutputDtoDocument() : new OutputDto();
        $outputDto->id = $data->id;
        $outputDto->baz = $data->num;
        $outputDto->bat = $data->str;
        $outputDto->relatedDummies = $data->relatedDummies instanceof Collection ? $data->relatedDummies : (array) $data->relatedDummies;

        return $outputDto;
    }
}
