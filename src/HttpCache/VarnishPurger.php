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
    private const DEFAULT_VARNISH_MAX_HEADER_LENGTH = 8000;

    private $clients;
    private $maxHeaderLength;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(array $clients, int $maxHeaderLength = self::DEFAULT_VARNISH_MAX_HEADER_LENGTH)
    {
        $this->clients = $clients;
        $this->maxHeaderLength = $maxHeaderLength;
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
        $parts = array_map(static function ($iri) {
            return sprintf('(^|\,)%s($|\,)', preg_quote($iri));
        }, $iris);

        foreach ($this->chunkRegexParts($parts) as $regex) {
            $this->banRegex($regex);
        }
    }

    private function banRegex(string $regex): void
    {
        foreach ($this->clients as $client) {
            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
        }
    }

    private function chunkRegexParts(array $parts): iterable
    {
        if (1 === \count($parts)) {
            yield $parts[0];

            return;
        }

        $concatenatedParts = sprintf('(%s)', implode(")\n(", $parts));

        if (\strlen($concatenatedParts) <= $this->maxHeaderLength) {
            yield str_replace("\n", '|', $concatenatedParts);

            return;
        }

        $lastSeparator = strrpos(substr($concatenatedParts, 0, $this->maxHeaderLength + 1), "\n");

        $chunk = substr($concatenatedParts, 0, $lastSeparator);

        yield str_replace("\n", '|', $chunk);

        $nextParts = \array_slice($parts, substr_count($chunk, "\n") + 1);

        yield from $this->chunkRegexParts($nextParts);
    }
}
