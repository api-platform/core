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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoOutput as DummyDtoNoOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use Doctrine\Persistence\ManagerRegistry;

class DummyDtoNoOutputProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(object $data, Operation $operation = null, array $uriVariables = [], array $context = []): ?object
    {
        $isOrm = true;
        $em = $this->registry->getManagerForClass(DummyDtoNoOutput::class);
        if (null === $em) {
            $em = $this->registry->getManagerForClass(DummyDtoNoOutputDocument::class);
            $isOrm = false;
        }

        $output = $isOrm ? new DummyDtoNoOutput() : new DummyDtoNoOutputDocument();
        $output->lorem = $data->foo;
        $output->ipsum = (string) $data->bar;

        $em->persist($output);
        $em->flush();

        return $output;
    }
}
