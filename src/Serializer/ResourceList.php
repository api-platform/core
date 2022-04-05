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

namespace ApiPlatform\Serializer;

/**
 * @internal
 */
class ResourceList extends \ArrayObject
{
}

class_alias(ResourceList::class, \ApiPlatform\Core\Serializer\ResourceList::class);
