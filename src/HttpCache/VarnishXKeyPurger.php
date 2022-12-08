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

use ApiPlatform\HttpCache\TagsInvalidator\Purger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Varnish XKey.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated configure a {@see Purger} instead
 */
final class VarnishXKeyPurger extends Purger implements PurgerInterface
{
    private const VARNISH_MAX_HEADER_LENGTH = 8000;

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(private readonly array $clients, private readonly int $maxHeaderLength = self::VARNISH_MAX_HEADER_LENGTH, private readonly string $xkeyGlue = ' ')
    {
        parent::__construct($this->clients, 'xkey', $this->maxHeaderLength, $this->xkeyGlue);
    }

    public function purge(array $iris): void
    {
        $this->invalidate($iris);
    }

    public function getResponseHeaders(array $iris): array
    {
        return ['xkey' => implode($this->xkeyGlue, $iris)];
    }
}
