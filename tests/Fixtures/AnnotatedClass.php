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

namespace ApiPlatform\Core\Tests\Fixtures;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     shortName="shortName",
 *     description="description",
 *     iri="http://example.com/res",
 *     itemOperations={"foo"={"bar"}},
 *     collectionOperations={"bar"={"foo"}},
 *     graphql={"query"={"normalization_context"={"groups"={"foo", "bar"}}}},
 *     attributes={"foo"="bar", "route_prefix"="/whatever", "cache_headers"={"max_age"=0, "shared_max_age"=0}},
 *     routePrefix="/foo",
 *     accessControl="has_role('ROLE_FOO')",
 *     accessControlMessage="You are not foo."
 * )
 *
 * @author Marcus Speight <marcus@pmconnect.co.uk>
 */
class AnnotatedClass
{
}
