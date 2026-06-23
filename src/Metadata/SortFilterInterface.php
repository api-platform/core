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

namespace ApiPlatform\Metadata;

/**
 * Marks a filter that sorts a collection by one or more properties.
 *
 * Backend-agnostic so consumers can recognize a sort filter without depending
 * on a persistence layer: GraphQL, for instance, exposes such a parameter as an
 * ordered list of single-property inputs to preserve multi-key ordering, which
 * an (unordered) input object cannot express.
 */
interface SortFilterInterface
{
}
