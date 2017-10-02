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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Problem\Serializer\ErrorNormalizerTrait;
use Symfony\Component\Debug\Exception\FlattenException;
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

    use ErrorNormalizerTrait;

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
        if ($this->debug) {
            $trace = $object->getTrace();
        }

        $data = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Error']),
            '@type' => 'hydra:Error',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $this->getErrorMessage($object, $context, $this->debug),
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
