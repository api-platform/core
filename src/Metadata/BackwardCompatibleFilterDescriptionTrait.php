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
 * Lets a filter satisfy the legacy FilterInterface::getDescription() requirement without implementing it by hand.
 *
 * Use this trait in a filter that does not need to describe itself through the deprecated getDescription() mechanism:
 * it returns an empty array, which is the expected value now that filters are described through QueryParameter metadata.
 * The trait will be removed in 6.0 together with FilterInterface::getDescription().
 *
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
trait BackwardCompatibleFilterDescriptionTrait
{
    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
