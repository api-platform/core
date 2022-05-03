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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ODM\Document
 */
#[ApiResource]
class VoDummyDriver
{
    use VoDummyIdAwareTrait;

    public function __construct(
        /**
         * @ODM\Field
         */
        #[Groups(['car_read', 'car_write'])] private readonly string $firstName,
        /**
         * @ODM\Field
         */
        #[Groups(['car_read', 'car_write'])] private readonly string $lastName
    ) {
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
}
