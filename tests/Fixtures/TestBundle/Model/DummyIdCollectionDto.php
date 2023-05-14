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
 * Dummy resource with an identifier, with a GetCollection with an output, and without any other operations.
 */
class DummyIdCollectionDto extends DummyCollectionDto
{
    public $id;

    public function __construct($id, $text)
    {
        parent::__construct($text);

        $this->id = $id;
    }
}
