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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\CustomIdentifierDummy as CustomIdentifierDummyModel;

/**
 * Custom Identifier Dummy.
 *
 * @ApiResource(dataModel=CustomIdentifierDummyModel::class)
 */
class CustomIdentifierDummy
{
    /**
     * @var int The custom identifier
     *
     * @ApiProperty(identifier=true)
     */
    private $customId;

    /**
     * @var string The dummy name
     */
    private $name;

    /**
     * @param int $customId
     */
    public function __construct($customId = null)
    {
        $this->customId = $customId;
    }

    /**
     * @return int
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
