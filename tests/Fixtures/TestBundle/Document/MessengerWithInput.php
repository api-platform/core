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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\MessengerInput;

/**
 * @ApiResource(messenger="input", input=MessengerInput::class, graphql={"create"={"input"=MessengerInput::class, "messenger"="input"}})
 */
class MessengerWithInput
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id;
    /**
     * @var string
     */
    public $name;
}
