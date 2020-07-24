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

namespace ApiPlatform\Core\DataPersister;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface HandOverDataPersisterInterface
{
    /**
     * Should the chain data persister continue to the next one?
     */
    public function handOver($data, array $context = []): bool;
}
