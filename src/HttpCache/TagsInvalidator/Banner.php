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

class Banner implements TagsInvalidatorInterface
{
    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(
        private readonly array $clients,
        private readonly string $headerName,
        private readonly int $maxHeaderLength,
    ) {
    }

    public function invalidate(array $tags): void
    {
        foreach ($this->yieldHeaders($tags) as $header) {
            foreach ($this->clients as $client) {
                $client->request('BAN', '', ['headers' => [$this->headerName => $header]]);
            }
        }
    }

    private function yieldHeaders(array $tags): \Iterator
    {
        if (!$tags) {
            return;
        }

        $tag = reset($tags);

        while (true) {
            $header = '';
            $buffer = preg_quote($tag);

            while (\strlen($buffer) + 8 <= $this->maxHeaderLength) {
                $tag = next($tags);

                if (null === key($tags)) { // @phpstan-ignore-line yes, key() can return null
                    yield '('.$buffer.')($|\,)';

                    return;
                }

                $header = $buffer;
                $buffer .= '|'.preg_quote($tag);
            }

            if (!$header) {
                throw new \Exception(sprintf('IRI "%s" is too long to fit the max header size (currently set to "%s").', $tag, $this->maxHeaderLength));
            }

            yield "({$header})($|\,)";
        }
    }
}
