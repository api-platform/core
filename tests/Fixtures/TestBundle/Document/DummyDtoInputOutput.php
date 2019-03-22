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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"input"=InputDto::class, "output"=OutputDto::class})
 * @ODM\Document
 */
class DummyDtoInputOutput
{
    /**
     * @var int The id
     * @ODM\Id(strategy="INCREMENT", type="integer", nullable=true)
     */
    public $id;

    /**
     * @var int The id
     * @ODM\Field
     */
    public $str;

    /**
     * @var int The id
     * @ODM\Field(type="float")
     */
    public $num;
}
