<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * JSON-LD Response.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Response extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    protected function update()
    {
        // Only set the header when there is none or when it equals 'application/ld+json' (from a previous update with callback)
        // in order to not overwrite a custom definition.
        if (!$this->headers->has('Content-Type') || 'application/ld+json' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/ld+json');
        }

        return $this->setContent($this->data);
    }
}
