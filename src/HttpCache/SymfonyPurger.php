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

use Toflar\Psr6HttpCacheStore\Psr6StoreInterface;

/**
 * Purges Symfony's HttpCache.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
final class SymfonyPurger implements PurgerInterface
{
    /**
     * @var StoreAwareKernelInterface
     */
    private $kernel;

    /**
     * @param StoreAwareKernelInterface $kernel
     */
    public function __construct(StoreAwareKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {
        if (!$iris) {
            return;
        }

        $store = $this->kernel->getStore();

        if (!$store instanceof Psr6StoreInterface) {
            return;
        }

        // Encode tags for greater compatiblity with different proxies
        // Some do not allow special characters like / or @ in cache tags and
        // also it allows to use a , in a tag, if you wish to do so.
        $iris = array_map(function($resource) {
            return base64_encode($resource);
        }, $iris);

        $store->invalidateTags($iris);
    }
}
