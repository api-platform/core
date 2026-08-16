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

namespace ApiPlatform\Elasticsearch\State;

/**
 * The query language used to query Elasticsearch.
 *
 * @experimental
 */
enum QueryLanguage: string
{
    /**
     * The classic Query DSL, sent to the "_search" endpoint.
     */
    case Dsl = 'dsl';

    /**
     * ES|QL, sent to the "_query" endpoint (requires Elasticsearch >= 8.14).
     */
    case Esql = 'esql';
}
