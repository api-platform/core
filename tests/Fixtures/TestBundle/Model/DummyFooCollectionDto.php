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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

/**
 * Dummy resource without identifier, with a GetCollection with an output and itemUriTemplate.
 */
class DummyFooCollectionDto
{
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
