<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonApi\Serializer;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonapi';

    private $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $message = $object->getMessage();

        if ($this->debug) {
            $trace = $object->getTrace();
        } elseif ($object instanceof FlattenException) {
            $statusCode = $context['statusCode'] ?? $object->getStatusCode();

            if ($statusCode >= 500 && $statusCode < 600) {
                $message = Response::$statusTexts[$statusCode];
            }
        }

        $data = [
            'title' => $context['title'] ?? 'An error occurred',
            'description' => $message ?? (string) $object,
        ];

        if (isset($trace)) {
            $data['trace'] = $trace;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }
}
