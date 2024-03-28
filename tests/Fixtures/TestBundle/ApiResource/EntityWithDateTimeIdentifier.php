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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

#[ApiResource(provider: [self::class, 'provide'])]
class EntityWithDateTimeIdentifier
{
    #[ApiProperty(identifier: true)]
    private ?\DateTimeInterface $day;

    public function __construct($day)
    {
        $this->setDay($day);
    }

    public function getDay(): ?\DateTimeInterface
    {
        return $this->day;
    }

    public function setDay(?\DateTimeInterface $day): void
    {
        $this->day = $day;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $context;
    }
}
