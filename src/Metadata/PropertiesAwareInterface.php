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
 * This interface makes a Parameter aware of the properties it can filter on.
 * It can be set on a Filter or a Parameter, properties are available in
 * extraProperties['_properties'].
 */
interface PropertiesAwareInterface
{
}
