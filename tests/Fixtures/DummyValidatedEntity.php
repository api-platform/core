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

namespace ApiPlatform\Tests\Fixtures;

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
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 4, min: 10)]
    #[Assert\Regex(pattern: '/^dummy$/')]
    public $dummy;

    /**
     * @var string
     */
    #[Assert\Email]
    #[Assert\NotBlank(allowNull: true)]
    public $dummyEmail;

    /**
     * @var string
     */
    #[Assert\Uuid]
    public $dummyUuid;

    /**
     * @var string
     */
    #[Assert\Ip]
    public $dummyIpv4;

    /**
     * @var string
     */
    #[Assert\Ip(version: '6')]
    public $dummyIpv6;

    /**
     * @var \DateTimeInterface A dummy date
     */
    #[Assert\Date]
    public $dummyDate;

    /**
     * @var string A dummy group
     */
    #[Assert\NotNull(groups: ['dummy'])]
    public $dummyGroup;

    /**
     * @var string A dummy url
     */
    #[Assert\Url]
    public $dummyUrl;
}
