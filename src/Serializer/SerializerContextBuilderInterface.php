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

use ApiPlatform\State\SerializerContextBuilderInterface as StateSerializerContextBuilderInterface;

/**
 * Builds the context used by the Symfony Serializer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface SerializerContextBuilderInterface extends StateSerializerContextBuilderInterface
{
}
