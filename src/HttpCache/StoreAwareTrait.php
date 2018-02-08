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

/**
 * StoreAwareTrait
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
trait StoreAwareTrait
{
    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @return StoreInterface
     */
    public function getStore(): StoreInterface
    {
        return $this->store;
    }

    /**
     * @param StoreInterface $store
     */
    public function setStore(StoreInterface $store): void
    {
        $this->store = $store;
    }
}
