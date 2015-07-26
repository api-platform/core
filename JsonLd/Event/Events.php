<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Event;

/**
 * JSON-LD Events.
 *
 * @author Luc Vieillescazes <luc@vieillescazes.net>
 */
final class Events
{
    /**
     * The CONTEXT_BUILDER event occurs after the creation of the context.
     */
    const CONTEXT_BUILDER = 'api.jsonld.context_builder';
}
