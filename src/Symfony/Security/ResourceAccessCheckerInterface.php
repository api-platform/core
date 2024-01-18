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

namespace ApiPlatform\Symfony\Security;

use ApiPlatform\Metadata\ResourceAccessCheckerInterface as MetadataResourceAccessCheckerInterface;

/**
 * Checks if the logged user has sufficient permissions to access the given resource.
 *
 * @deprecated use \ApiPlatform\Metadata\ResourceAccessCheckerInterface instead
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceAccessCheckerInterface extends MetadataResourceAccessCheckerInterface
{
}
