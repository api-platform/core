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
 * Dummy Iri filled with validation Entity.
 *
 * @author Grégoire Hébert <gregoire@les-tilleuls.coop>
 */
class DummyIriWithValidationEntity
{
    /**
     * @Assert\Url
     */
    public $dummyUrl;

    /**
     * @Assert\Email
     */
    public $dummyEmail;

    /**
     * @Assert\Uuid
     */
    public $dummyUuid;

    /**
     * @Assert\CardScheme(schemes="MASTERCARD")
     */
    public $dummyCardScheme;

    /**
     * @Assert\Bic
     */
    public $dummyBic;

    /**
     * @Assert\Iban
     */
    public $dummyIban;

    /**
     * @Assert\Date
     */
    public $dummyDate;

    /**
     * @Assert\DateTime
     */
    public $dummyDateTime;

    /**
     * @Assert\Time
     */
    public $dummyTime;

    /**
     * @Assert\Image
     */
    public $dummyImage;

    /**
     * @Assert\File
     */
    public $dummyFile;

    /**
     * @Assert\Currency
     */
    public $dummyCurrency;

    /**
     * @Assert\Isbn
     */
    public $dummyIsbn;

    /**
     * @Assert\Issn
     */
    public $dummyIssn;
}
