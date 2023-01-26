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

namespace ApiPlatform\HttpCache;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Varnish XKey.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class VarnishXKeyPurger extends SurrogateKeysPurger
{
    private const VARNISH_MAX_HEADER_LENGTH = 8000;
    private const VARNISH_SEPARATOR = ' ';

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(iterable $clients, int $maxHeaderLength = self::VARNISH_MAX_HEADER_LENGTH, string $xkeyGlue = self::VARNISH_SEPARATOR)
    {
        parent::__construct($clients, $maxHeaderLength, 'xkey', $xkeyGlue);
    }
}
