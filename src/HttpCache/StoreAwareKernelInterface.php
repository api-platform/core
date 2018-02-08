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

namespace ApiPlatform\Core\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Interface to implement in your HttpCache kernel and make it HttpCache store
 * aware.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
interface StoreAwareKernelInterface extends KernelInterface
{
    /**
     * @param StoreInterface $store
     */
    public function setStore(StoreInterface $store): void;

    /**
     * @return StoreInterface
     */
    public function getStore(): StoreInterface;
}
