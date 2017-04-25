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

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Problem\Serializer\ErrorNormalizerTrait;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonapi';

    use ErrorNormalizerTrait;

    private $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        if ($this->debug) {
            $trace = $object->getTrace();
        }

        $message = $object->getErrorMessage($object, $context, $this->debug);

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
