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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\InputDto;
use Doctrine\Common\Persistence\ManagerRegistry;

class DummyDtoNoOutputDataPersister implements DataPersisterInterface
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $data instanceof InputDto;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        $output = new DummyDtoNoOutput();
        $output->lorem = $data->foo;
        $output->ipsum = (string) $data->bar;

        $em = $this->registry->getManagerForClass(DummyDtoNoOutput::class);
        $em->persist($output);
        $em->flush();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        return null;
    }
}
