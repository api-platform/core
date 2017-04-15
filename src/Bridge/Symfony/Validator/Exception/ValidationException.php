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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationException extends \RuntimeException
{
    private $constraintViolationList;

    public function __construct(ConstraintViolationListInterface $constraintViolationList, $message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->constraintViolationList = $constraintViolationList;
    }

    /**
     * Gets constraint violations related to this exception.
     *
     * @return ConstraintViolationListInterface
     */
    public function getConstraintViolationList()
    {
        return $this->constraintViolationList;
    }
}
