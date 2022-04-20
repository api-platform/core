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

namespace ApiPlatform\Core\JsonApi\EventListener;

class_exists(\ApiPlatform\Symfony\EventListener\JsonApi\TransformFilteringParametersListener::class);

if (false) {
    final class TransformFilteringParametersListener extends \ApiPlatform\Symfony\EventListener\JsonApi\TransformFilteringParametersListener
    {
    }
}
