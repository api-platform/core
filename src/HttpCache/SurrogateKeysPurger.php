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

use ApiPlatform\Metadata\Exception\RuntimeException;
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

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(protected readonly iterable $clients, protected readonly int $maxHeaderLength = self::MAX_HEADER_SIZE_PER_BATCH, protected readonly string $header = self::HEADER, protected readonly string $separator = self::SEPARATOR)
    {
    }

    /**
     * @return \Iterator<string>
     */
    private function getChunkedIris(array $iris): \Iterator
    {
        if (!$iris) {
            return;
        }

        $chunk = array_shift($iris);
        foreach ($iris as $iri) {
            $nextChunk = \sprintf('%s%s%s', $chunk, $this->separator, $iri);
            if (\strlen($nextChunk) <= $this->maxHeaderLength) {
                $chunk = $nextChunk;
                continue;
            }

            yield $chunk;
            $chunk = $iri;
        }

        yield $chunk;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris): void
    {
        foreach ($this->getChunkedIris($iris) as $chunk) {
            if (\strlen((string) $chunk) > $this->maxHeaderLength) {
                throw new RuntimeException(\sprintf('IRI "%s" is too long to fit current max header length (currently set to "%s"). You can increase it using the "api_platform.http_cache.invalidation.max_header_length" parameter.', $chunk, $this->maxHeaderLength));
            }

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
