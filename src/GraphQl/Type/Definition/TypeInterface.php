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

namespace ApiPlatform\Core\GraphQl\Type\Definition;

use GraphQL\Type\Definition\LeafType;

/**
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TypeInterface extends LeafType
{
    public function getName(): string;
}
