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

class_exists(\ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait::class);

if (false) {
    trait SearchFilterTrait
    {
        use \ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait;
    }
}
