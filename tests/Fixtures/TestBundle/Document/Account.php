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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document()]
#[ApiResource(
    operations: [
        new Get(security: 'object.getUser()  == user'),
        new GetCollection(),
    ],
)]
class Account
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: Settings::class, cascade: ['persist'], storeAs: 'id')]
    private ?Settings $settings = null;

    public function getId(): ?int
    {
        return $this->id;
    }

     public function getSettings(): ?Settings
     {
         return $this->settings;
     }

     public function setSettings(?Settings $settings): self
     {
         $this->settings = $settings;

         return $this;
     }
}
