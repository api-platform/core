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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/* @TODO remove this check in 3.0 */
if (\PHP_VERSION_ID >= 70200 && class_exists(Uuid::class) && class_exists(UuidType::class)) {
    /**
     * @ORM\Entity
     * @ApiResource
     *
     * @author Vincent Chalamon <vincentchalamon@gmail.com>
     */
    class SymfonyUuidDummy
    {
        /**
         * @ORM\Id
         * @ORM\Column(type="symfony_uuid", unique=true)
         * @ORM\GeneratedValue(strategy="NONE")
         */
        private $id;

        /**
         * @ORM\Column(nullable=true)
         */
        private $number;

        public function __construct($id = null)
        {
            $this->id = $id ?? Uuid::v4();
        }

        public function getId(): Uuid
        {
            return $this->id;
        }

        public function getNumber(): ?string
        {
            return $this->number;
        }

        public function setNumber(?string $number): self
        {
            $this->number = $number;

            return $this;
        }
    }
}
