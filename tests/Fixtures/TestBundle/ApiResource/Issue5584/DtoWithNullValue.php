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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5584;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ApiResource(denormalizationContext: [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true])]
final class DtoWithNullValue
{
    public \stdClass $dummy;
}
