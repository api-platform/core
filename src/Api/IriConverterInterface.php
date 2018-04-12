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

namespace ApiPlatform\Core\Api;

/**
 * Converts item and resources to IRI and vice versa.
 *
 * @deprecated IriConverterInterface is deprecated since API Platform 2.3 and will be removed in API Platform 3.0, use ItemToIriConverterInterface and/or IriToItemConverterInterface instead.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Mathieu Dewet <mathieu.dewet@gmail.com>
 */
interface IriConverterInterface extends ItemToIriConverterInterface, IriToItemConverterInterface
{
}
