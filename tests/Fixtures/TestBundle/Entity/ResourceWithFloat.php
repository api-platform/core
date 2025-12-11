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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class ResourceWithFloat
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'float')]
    private string|float $myFloatField = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMyFloatField(): string|float
    {
        // php 8.5 emits warning unexpected NAN value was coerced to string
        // with symfony serializer
        // @see https://github.com/symfony/symfony/pull/62740
        return is_nan($this->myFloatField) ? 'NAN' : $this->myFloatField;
    }

    public function setMyFloatField(float $myFloatField): void
    {
        // When binding a NAN value to a prepared statement parameter with Doctrine,
        // PHP 8.5 emits a warning: "unexpected NAN value was coerced to string".
        // @see https://github.com/doctrine/dbal/pull/7249
        $this->myFloatField = is_nan($myFloatField) ? 'NAN' : $myFloatField;
    }
}
