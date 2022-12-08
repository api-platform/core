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

namespace ApiPlatform\HttpCache\TagsInvalidator;

use ApiPlatform\HttpCache\TagsInvalidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Purger implements TagsInvalidatorInterface
{
    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(
        private readonly array $clients,
        private readonly string $headerName,
        private readonly int $maxHeaderLength,
        private readonly string $glue,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $tags): void
    {
        foreach ($this->yieldHeaders($tags) as $header) {
            foreach ($this->clients as $client) {
                $client->request('PURGE', '', ['headers' => [$this->headerName => $header]]);
            }
        }
    }

    /**
     * Yield the tags in the minimum number of header values.
     *
     * @param string[] $tags
     *
     *@throws \Exception if a key is longer than the longest configured header value
     *
     * @return \Iterator<string|string[]>
     */
    private function yieldHeaders(array $tags): \Iterator
    {
        if (!$tags) {
            return;
        }

        $header = implode($this->glue, $tags);
        if (\strlen($header) <= $this->maxHeaderLength) {
            yield $header;

            return;
        }

        $glueLength = \strlen($this->glue);
        $offset = 0;
        $tag = reset($tags);
        $tagLength = \strlen($tag);

        while (true) {
            $length = 0;
            $bufferLength = $tagLength;

            while ($bufferLength <= $this->maxHeaderLength) {
                $tag = next($tags);
                if (null === key($tags)) { // @phpstan-ignore-line yes, key() can return null
                    yield substr($header, $offset);

                    return;
                }

                $length = $bufferLength;
                $tagLength = \strlen($tag);
                $bufferLength += $glueLength + $tagLength;
            }

            if (!$length) {
                throw new \Exception(sprintf('IRI "%s" is too long to fit the max header size (currently set to "%s").', $tag, $this->maxHeaderLength));
            }

            yield substr($header, $offset, $length);

            $offset += $length + $glueLength;
        }
    }
}
