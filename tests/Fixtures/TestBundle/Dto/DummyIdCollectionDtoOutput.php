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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

class DummyIdCollectionDtoOutput extends DummyCollectionDtoOutput
{
    /**
     * @var int
     */
    public $id;

    public function __construct(int $id, string $foo, int $bar)
    {
        parent::__construct($foo, $bar);

        $this->id = $id;
    }
}
