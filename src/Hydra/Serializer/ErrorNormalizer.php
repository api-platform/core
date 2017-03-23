<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts {@see \Exception} or {@see \Symfony\Component\Debug\Exception\FlattenException} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class ErrorNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    private $urlGenerator;
    private $debug;

    public function __construct(UrlGeneratorInterface $urlGenerator, bool $debug = false)
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
        } elseif ($object instanceof FlattenException) {
            $statusCode = $context['statusCode'] ?? $object->getStatusCode();
            if ($statusCode >= 500 && $statusCode < 600) {
                $message = Response::$statusTexts[$statusCode];
            }
        }

        $data = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Error']),
            '@type' => 'hydra:Error',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $message ?? (string) $object,
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
