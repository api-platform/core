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

namespace ApiPlatform\Core\Bridge\GedmoDoctrine\Swagger;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class SwaggerFromDecorator.
 *
 * @author Ryan Jefferson <ryanhjefferson@gmail.com>
 */
final class SwaggerFromDecorator implements NormalizerInterface
{
    private $decorated;
    private $unmatched = [];

    public function __construct(NormalizerInterface $decorated, array $unmatched = null)
    {
        $this->decorated = $decorated;
        if (!empty($unmatched)) {
            $this->unmatched = $unmatched;
        }
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        $requestHeader = [
            'name' => 'From',
            'description' => 'Email address for a user who controls the requesting user agent.',
            'required' => false,
            'in' => 'header',
        ];

        foreach ($docs['paths'] as $name => &$item) {
            foreach ($this->unmatched as $unmatch) {
                if (preg_match($unmatch, $name)) {
                    continue 2;
                }
            }

            if (isset($item['post'])) {
                array_unshift($item['post']['parameters'], $requestHeader);
            }
            if (isset($item['patch'])) {
                array_unshift($item['patch']['parameters'], $requestHeader);
            }
        }

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
