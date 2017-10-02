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

/**
 * Dummy Validated Entity.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class DummyValidatedEntity
{
    /**
     * @var int A dummy ID
     */
    public $dummyId;

    /**
     * @var string A dummy
     *
     * @Assert\NotBlank
     */
    public $dummy;

    /**
     * @var \DateTimeInterface A dummy date
     *
     * @Assert\Date
     */
    public $dummyDate;

    /**
     * @var string A dummy group
     *
     * @Assert\NotNull(groups={"dummy"})
     */
    public $dummyGroup;
}
