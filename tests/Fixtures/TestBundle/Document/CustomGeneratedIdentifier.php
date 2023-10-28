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
use ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Generator\DocumentUuidGenerator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Custom identifier.
 */
#[ApiResource]
#[ODM\Document]
class CustomGeneratedIdentifier
{
    #[ODM\Id(strategy: 'CUSTOM', type: 'string', options: ['class' => DocumentUuidGenerator::class])]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }
}
