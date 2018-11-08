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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyOutput;

class DummyInputDataPersister implements DataPersisterInterface
{
    public function supports($data): bool
    {
        return $data instanceof DummyInput;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        $output = new DummyOutput();
        $output->name = $data->name;
        $output->id = 1;

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
