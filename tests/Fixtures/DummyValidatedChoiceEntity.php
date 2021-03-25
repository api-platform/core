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

namespace ApiPlatform\Core\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

class DummyValidatedChoiceEntity
{
    /**
     * @var string
     *
     * @Assert\Choice(choices={"a", "b"})
     */
    public $dummySingleChoice;

    /**
     * @var string
     *
     * @Assert\Choice(callback={DummyValidatedChoiceEntity::class, "getChoices"})
     */
    public $dummySingleChoiceCallback;

    /**
     * @var string[]
     *
     * @Assert\Choice(choices={"a", "b"}, multiple=true)
     */
    public $dummyMultiChoice;

    /**
     * @var string[]
     *
     * @Assert\Choice(callback={DummyValidatedChoiceEntity::class, "getChoices"}, multiple=true)
     */
    public $dummyMultiChoiceCallback;

    /**
     * @var string[]
     *
     * @Assert\Choice(choices={"a", "b", "c", "d"}, multiple=true, min=2)
     */
    public $dummyMultiChoiceMin;

    /**
     * @var string[]
     *
     * @Assert\Choice(choices={"a", "b", "c", "d"}, multiple=true, max=4)
     */
    public $dummyMultiChoiceMax;

    /**
     * @var string[]
     *
     * @Assert\Choice(choices={"a", "b", "c", "d"}, multiple=true, min=2, max=4)
     */
    public $dummyMultiChoiceMinMax;

    public static function getChoices(): array
    {
        return ['a', 'b', 'c', 'd'];
    }
}
