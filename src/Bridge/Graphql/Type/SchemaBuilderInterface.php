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

namespace ApiPlatform\Core\Bridge\Graphql\Type;

use GraphQL\Type\Schema;

/**
 * Builds a GraphQL schema.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
interface SchemaBuilderInterface
{
    public function getSchema(): Schema;
}
