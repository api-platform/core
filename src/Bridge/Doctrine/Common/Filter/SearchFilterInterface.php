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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

/**
 * Interface for filtering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SearchFilterInterface
{
    /**
     * @var string Exact matching
     */
    const STRATEGY_EXACT = 'exact';

    /**
     * @var string The value must be contained in the field
     */
    const STRATEGY_PARTIAL = 'partial';

    /**
     * @var string Finds fields that are starting with the value
     */
    const STRATEGY_START = 'start';

    /**
     * @var string Finds fields that are ending with the value
     */
    const STRATEGY_END = 'end';

    /**
     * @var string Finds fields that are starting with the word
     */
    const STRATEGY_WORD_START = 'word_start';
}
