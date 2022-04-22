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
    public const REGEX_PATTERN = '/\d(.*)/';

    /**
     * Detect whether the current ES version supports passing mapping type as a search parameter.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/removal-of-types.html#_schedule_for_removal_of_mapping_types
     */
    public static function supportsMappingType(string $version = Client::VERSION): bool
    {
        $matchResult = preg_match(self::REGEX_PATTERN, $version, $matches);

        return \is_int($matchResult) && $matchResult > 0 && (int) $matches[0] < 7;
    }
}
