<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Exception thrown when the user goes over their rate limit.
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
class RateLimitExceededException extends BadRequestHttpException implements ExceptionInterface
{
    /**
     * Sets default message.
     *
     * @param string          $message
     * @param \Exception|null $previous
     * @param int             $code
     * @param array           $headers
     */
    public function __construct(string $message = 'Rate limit exceeded.', \Exception $previous = null, int $code = 0, array $headers = array())
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
