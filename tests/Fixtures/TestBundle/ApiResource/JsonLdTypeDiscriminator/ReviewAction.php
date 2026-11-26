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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLdTypeDiscriminator;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: '@type', mapping: [
    'https://schema.org/LikeAction' => LikeAction::class,
    'https://schema.org/ShareAction' => ShareAction::class,
])]
abstract class ReviewAction
{
    public ?string $actionStatus = null;
}
