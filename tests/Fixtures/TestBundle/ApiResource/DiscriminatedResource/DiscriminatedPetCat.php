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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'breed', mapping: [
    'persian' => DiscriminatedPersianCat::class,
    'siamese' => DiscriminatedSiameseCat::class,
])]
abstract class DiscriminatedPetCat extends DiscriminatedPet
{
    public int $lives = 9;
}
