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

namespace ApiPlatform\Core\Bridge\GedmoDoctrine\Model;

use Symfony\Component\HttpFoundation\Request;

/**
 * A model for holding the email address sent in a "From" header request.
 *
 * @author Ryan Jefferson <ryanhjefferson@gmail.com>
 */
class FromUser
{
    private $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->getEmail();
    }
}
