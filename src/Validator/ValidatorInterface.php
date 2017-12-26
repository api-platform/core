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

namespace ApiPlatform\Core\Validator;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;

/**
 * Validates an item using the Symfony validator component.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ValidatorInterface
{
    /**
     * Validates an item.
     *
     * @param object $data
     *
     * @throws ValidationException
     */
    public function validate($data, array $context = []);
}
