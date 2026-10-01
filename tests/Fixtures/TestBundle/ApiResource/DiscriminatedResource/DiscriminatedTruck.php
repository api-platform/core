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

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class DiscriminatedTruck extends DiscriminatedVehicle
{
    #[Assert\Length(min: 5)]
    public string $name = '';

    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    public ?string $plate = null;

    public ?int $payload = null;

    /**
     * The setter cannot narrow its parameter (contravariance): writes are narrowed through validation.
     */
    #[Assert\Type(self::class)]
    protected ?DiscriminatedVehicle $trailer = null;

    /**
     * Covariant return type: a truck only tows trucks.
     */
    public function getTrailer(): ?self
    {
        \assert(null === $this->trailer || $this->trailer instanceof self);

        return $this->trailer;
    }

    public function getRating(): int
    {
        return 4;
    }
}
