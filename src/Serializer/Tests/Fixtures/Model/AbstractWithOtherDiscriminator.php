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

namespace ApiPlatform\Serializer\Tests\Fixtures\Model;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'kind', mapping: ['other' => ConcreteWithDiscriminator::class], defaultType: 'other')]
abstract class AbstractWithOtherDiscriminator
{
}
