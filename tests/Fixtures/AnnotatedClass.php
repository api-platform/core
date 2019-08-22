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
 *     attributes={"foo"="bar", "route_prefix"="/whatever", "cache_headers"={"max_age"=0, "shared_max_age"=0, "vary"={"Custom-Vary-1", "Custom-Vary-2"}}},
 *     routePrefix="/foo",
 *     security="is_granted('ROLE_FOO')",
 *     securityMessage="You are not foo.",
 *     securityPostDenormalize="is_granted('ROLE_BAR')",
 *     securityPostDenormalizeMessage="You are not bar."
 * )
 *
 * @author Marcus Speight <marcus@pmconnect.co.uk>
 */
class AnnotatedClass
{
}
