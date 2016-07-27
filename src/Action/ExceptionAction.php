<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Action;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\ErrorFormatGuesser;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Renders a normalized exception for a given {@see \Symfony\Component\Debug\Exception\FlattenException}.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionAction
{
    const DEFAULT_EXCEPTION_TO_STATUS = [
        ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
    ];

    private $serializer;
    private $errorFormats;
    private $exceptionToStatus;

    public function __construct(SerializerInterface $serializer, array $errorFormats, $exceptionToStatus = [])
    {
        $this->serializer = $serializer;
        $this->errorFormats = $errorFormats;
        $this->exceptionToStatus = array_merge(self::DEFAULT_EXCEPTION_TO_STATUS, $exceptionToStatus);
    }

    /**
     * Converts a an exception to a JSON response.
     *
     * @param FlattenException $exception
     * @param Request          $request
     *
     * @return Response
     */
    public function __invoke(FlattenException $exception, Request $request) : Response
    {
        $exceptionClass = $exception->getClass();
        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $exception->setStatusCode($status);

                break;
            }
        }

        $headers = $exception->getHeaders();
        $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);
        $headers['Content-Type'] = $format['value'][0];

        return new Response($this->serializer->serialize($exception, $format['key']), $exception->getStatusCode(), $headers);
    }
}
