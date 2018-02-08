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

use GuzzleHttp\ClientInterface;

/**
 * Purges Varnish.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class VarnishPurger implements PurgerInterface
{
    private $clients;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(array $clients)
    {
        $this->clients = $clients;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {
        if (!$iris) {
            return;
        }

        // Create the regex to purge all tags in just one request
        $parts = array_map(function ($iri) {
            // Encode tags for greater compatiblity with different proxies
            // Some do not allow special characters like / or @ in cache tags and
            // also it allows to use a , in a tag, if you wish to do so.
            return sprintf('(^|\,)%s($|\,)', base64_encode($iri));
        }, $iris);

        $regex = \count($parts) > 1 ? sprintf('(%s)', implode(')|(', $parts)) : array_shift($parts);

        foreach ($this->clients as $client) {
            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
        }
    }
}
