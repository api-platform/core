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
 * Surrogate keys purger.
 *
 * @author Sylvain Combraque <darkweak@protonmail.com>
 */
class SurrogateKeysPurger implements PurgerInterface
{
    private const MAX_HEADER_SIZE_PER_BATCH = 1500;
    private const SEPARATOR = ', ';
    private const HEADER = 'Surrogate-Key';
    protected int $separatorLength = 2;

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(protected readonly array $clients, protected readonly int $maxHeaderLength = self::MAX_HEADER_SIZE_PER_BATCH, protected readonly string $header = self::HEADER, protected readonly string $separator = self::SEPARATOR)
    {
        $this->separatorLength = \strlen($this->separator);
    }

    /**
     * @return string[]
     */
    private function getChunkedIris(array $iris): array
    {
        $str = implode($this->separator, $iris);
        $batches = [];

        while (\strlen($str) > $this->maxHeaderLength) {
            $splitPosition = strrpos(str_split($str, $this->maxHeaderLength)[0], $this->separator);
            if ($splitPosition) {
                [$batches[], $str] = str_split($str, $splitPosition);
                $str = substr($str, $this->separatorLength);
            }
        }

        $batches[] = $str;

        return $batches;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris): void
    {
        foreach ($this->getChunkedIris($iris) as $chunk) {
            foreach ($this->clients as $client) {
                $client->request(
                    Request::METHOD_PURGE,
                    '',
                    ['headers' => [$this->header => $chunk]]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders(array $iris): array
    {
        return [$this->header => implode($this->separator, $iris)];
    }
}
