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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DoctrineDiscriminatedSportsCar extends DoctrineDiscriminatedCar
{
    #[ODM\Field(type: 'int')]
    public int $topSpeed = 0;
}
