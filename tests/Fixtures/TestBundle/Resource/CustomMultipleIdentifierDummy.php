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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\CustomMultipleIdentifierDummy as CustomMultipleIdentifierDummyModel;

/**
 * Custom Identifier Dummy.
 *
 * @ApiResource(dataModel=CustomMultipleIdentifierDummyModel::class, compositeIdentifier=false)
 */
class CustomMultipleIdentifierDummy
{
    /**
     * @var int The custom identifier
     *
     * @ApiProperty(identifier=true)
     */
    private $firstId;

    /**
     * @var int The custom identifier
     *
     * @ApiProperty(identifier=true)
     */
    private $secondId;

    /**
     * @var string The dummy name
     */
    private $name;

    public function getFirstId(): int
    {
        return $this->firstId;
    }

    public function setFirstId(int $firstId)
    {
        $this->firstId = $firstId;
    }

    public function getSecondId(): int
    {
        return $this->secondId;
    }

    public function setSecondId(int $secondId)
    {
        $this->secondId = $secondId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}
