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

#[ApiResource]
#[ODM\Document]
class VoDummyInsuranceCompany
{
    use VoDummyIdAwareTrait;

    public function __construct(
        #[Groups(['car_read', 'car_write'])] #[ODM\Field] private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
