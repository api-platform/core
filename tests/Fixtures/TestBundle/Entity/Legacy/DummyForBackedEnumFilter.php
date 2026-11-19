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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\IntegerBackedEnum;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\StringBackedEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * Legacy regression fixture: keeps the deprecated #[ApiFilter(BackedEnumFilter)] attribute path
 * alive until 6.0. The canonical replacement lives at Entity\Issue7126\DummyForBackedEnumFilter
 * (QueryParameter + ExactFilter).
 */
#[GetCollection(
    uriTemplate: 'legacy_backed_enum_filter{._format}',
)]
#[ApiFilter(BackedEnumFilter::class, properties: ['stringBackedEnum', 'integerBackedEnum'])]
#[ORM\Entity]
class DummyForBackedEnumFilter
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: true, enumType: StringBackedEnum::class)]
    private ?StringBackedEnum $stringBackedEnum = null;

    #[ORM\Column(nullable: true, enumType: IntegerBackedEnum::class)]
    private ?IntegerBackedEnum $integerBackedEnum = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStringBackedEnum(): ?StringBackedEnum
    {
        return $this->stringBackedEnum;
    }

    public function setStringBackedEnum(StringBackedEnum $stringBackedEnum): void
    {
        $this->stringBackedEnum = $stringBackedEnum;
    }

    public function getIntegerBackedEnum(): ?IntegerBackedEnum
    {
        return $this->integerBackedEnum;
    }

    public function setIntegerBackedEnum(IntegerBackedEnum $integerBackedEnum): void
    {
        $this->integerBackedEnum = $integerBackedEnum;
    }
}
