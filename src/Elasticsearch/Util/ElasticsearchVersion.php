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

namespace ApiPlatform\Elasticsearch\Util;

use Elasticsearch\Client;

class ElasticsearchVersion
{
    /**
     * @see https://regex101.com/r/EWxpuO/2
     */
    public const REGEX_PATTERN = '/\d(.*)/';

    public static function supportsDocumentType(string $version = Client::VERSION): bool
    {
        $matchResult = preg_match(self::REGEX_PATTERN, $version, $matches);

        return is_int($matchResult) && $matchResult > 0 && (int) $matches[0] < 7;
    }
}
