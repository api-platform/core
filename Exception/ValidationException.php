<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationException extends \RuntimeException
{
    /**
     * @var ConstraintViolationListInterface
     */
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
