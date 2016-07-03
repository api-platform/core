<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts {@see \Exception} or {@see \Symfony\Component\Debug\Exception\FlattenException}
 * to a Swagger error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class SwaggerErrorNormalizer implements NormalizerInterface
{
    const FORMAT = 'swagger-error';

    private $urlGenerator;
    private $debug;

    public function __construct(UrlGeneratorInterface $urlGenerator, bool $debug)
    {
        $this->urlGenerator = $urlGenerator;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $message = $object->getMessage();

        if ($this->debug) {
            $trace = $object->getTrace();
        }

        $data = [
            'title' => $context['title'] ?? 'An error occurred',
            'description' => $message ?? (string) $object,
        ];
        if (isset($trace)) {
            $data['trace'] = $trace;
        }

        return 'toto';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }
}
