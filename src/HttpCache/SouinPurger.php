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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Souin.
 *
 * @author Sylvain Combraque <darkweak@protonmail.com>
 *
 * @experimental
 */
class SouinPurger implements PurgerInterface
{
    private const MAX_HEADER_SIZE_PER_BATCH = 1500;
    private const SEPARATOR = ', ';
    private const SEPARATOR_LENGTH = 2;
    private const HEADER = 'Surrogate-Key';

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(private readonly array $clients)
    {
    }

    private function getChunkedRegex(array $iris): array
    {
        $regex = implode(self::SEPARATOR, $iris);
        $batches = [];

        while (\strlen($regex) > self::MAX_HEADER_SIZE_PER_BATCH) {
            $splitPosition = strrpos(str_split($regex, self::MAX_HEADER_SIZE_PER_BATCH)[0], self::SEPARATOR);
            if ($splitPosition) {
                [$batches[], $regex] = str_split($regex, $splitPosition);
                $regex = substr($regex, self::SEPARATOR_LENGTH);
            }
        }

        $batches[] = $regex;

        return $batches;
    }

    private function banRegex(string $regex): void
    {
        foreach ($this->clients as $client) {
            $client->request(
                Request::METHOD_PURGE,
                '',
                ['headers' => [self::HEADER => $regex]]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris): void
    {
        foreach ($this->getChunkedRegex($iris) as $chunkedRegex) {
            $this->banRegex($chunkedRegex);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders(array $iris): array
    {
        return [self::HEADER => implode(self::SEPARATOR, $iris)];
    }
}
