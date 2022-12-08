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

use ApiPlatform\HttpCache\TagsInvalidator\Banner;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Varnish.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated use a {@see Banner} instead
 */
final class VarnishPurger extends Banner implements PurgerInterface
{
    private const DEFAULT_VARNISH_MAX_HEADER_LENGTH = 8000;

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(private readonly array $clients, int $maxHeaderLength = self::DEFAULT_VARNISH_MAX_HEADER_LENGTH)
    {
        parent::__construct($this->clients, 'ApiPlatform-Ban-Regex', $maxHeaderLength);
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris): void
    {
        $this->invalidate($iris);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders(array $iris): array
    {
        return ['Cache-Tags' => implode(',', $iris)];
    }
}
